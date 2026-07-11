"""
Phalcon Admin Deploy UI — 项目部署编排
"""
import os
from .ssh import SSHClient
from .config import DeployConfig
from . import docker as docker_ops
from . import git as git_ops
from . import router as router_ops


def init_project(ssh: SSHClient, config: DeployConfig, project_name: str,
                 mode: str = '', nginx_port: int = 8071) -> list[str]:
    """初始化部署项目"""
    results = []
    project_path = config.project_path
    repo = config.project_config.get('repo', '')
    branch = config.project_config.get('branch', 'main')

    if not project_path or not repo:
        results.append('[错误] 项目配置不完整，缺少 path 或 repo')
        return results

    # 1. 创建目录
    results.append(f'创建目录: {project_path}')
    ssh.exec(f"mkdir -p {project_path}")

    # 2. Git clone
    results.append(f'克隆仓库: {repo} ({branch})')
    ssh.exec(f"cd {project_path} && git clone {repo} . --branch {branch}")

    # 3. 配置上传
    results.append('上传配置文件...')
    _upload_configs(ssh, config, project_path, nginx_port)

    # 4. Docker up
    results.append('启动容器...')
    dc_cmd = docker_ops.get_compose_cmd(ssh)
    dc_file = docker_ops.get_compose_file(ssh, project_path)
    ssh.exec(f"cd {project_path} && {dc_cmd} -f {dc_file} up -d")
    results.append('容器已启动')

    # 5. Nginx 配置
    domains = config.domains
    if domains:
        actual_mode = mode or router_ops.detect_mode(ssh)
        router_ops.add_domain(ssh, project_name, domains, actual_mode, nginx_port)
        results.append('域名已配置')

    return results


def upgrade_project(ssh: SSHClient, config: DeployConfig) -> list[str]:
    """更新项目"""
    results = []
    project_path = config.project_path

    if not git_ops.git_check_exists(ssh, project_path):
        results.append(f'[错误] 项目不存在: {project_path}')
        return results

    results.append('拉取代码...')
    results.extend(git_ops.git_pull(ssh, project_path))

    results.append('重启容器...')
    dc_cmd = docker_ops.get_compose_cmd(ssh)
    dc_file = docker_ops.get_compose_file(ssh, project_path)
    ssh.exec(f"cd {project_path} && {dc_cmd} -f {dc_file} up -d")
    results.append('更新完成')

    return results


def reset_project(ssh: SSHClient, config: DeployConfig) -> list[str]:
    """重置项目代码"""
    results = []
    project_path = config.project_path

    if not git_ops.git_check_exists(ssh, project_path):
        results.append(f'[错误] 项目不存在: {project_path}')
        return results

    results.append('重置主仓库...')
    results.extend(git_ops.git_reset(ssh, project_path))

    # 重置模块
    modules = config.project_config.get('modules', {})
    for module_name in modules:
        module_path = f"{project_path}/src/App/Modules/{module_name}"
        code, out, _ = ssh.exec(f"test -d {module_path}/.git && echo 'YES' || echo 'NO'")
        if out.strip() == 'YES':
            results.append(f'重置模块: {module_name}')
            git_ops.git_reset(ssh, module_path)

    results.append('重置完成')
    return results


def push_configs(ssh: SSHClient, config: DeployConfig, local_deploys_dir: str) -> list[str]:
    """推送本地配置到远程"""
    results = []
    project_path = config.project_path
    project_name = config.project_config.get('name', '')

    # 本地项目配置目录
    local_project_dir = os.path.join(local_deploys_dir, 'projects', project_name)

    file_map = {
        '.env': f"{project_path}/.env",
        'docker-compose.yaml': f"{project_path}/docker-compose.yaml",
        'docker-compose.ports.yaml': f"{project_path}/docker-compose.ports.yaml",
        'docker/nginx/sites/default.conf': f"{project_path}/docker/nginx/sites/default.conf",
        'docker/php/php.ini': f"{project_path}/docker/php/php.ini",
        'docker/mysql/my.cnf': f"{project_path}/docker/mysql/my.cnf",
        'src/config/config.php': f"{project_path}/src/config/config.php",
    }

    found = False
    for local_rel, remote_path in file_map.items():
        local_file = os.path.join(local_project_dir, local_rel)
        if os.path.exists(local_file):
            with open(local_file, 'r', encoding='utf-8') as f:
                content = f.read()
            ssh.upload_content(content, remote_path)
            results.append(f"已上传: {local_rel}")
            found = True

    if not found:
        results.append('[警告] 未找到本地配置文件，跳过')

    return results


def _upload_configs(ssh: SSHClient, config: DeployConfig, project_path: str, nginx_port: int):
    """上传模板渲染后的配置（init 时使用）"""
    imports = """
    .env
    docker-compose.ports.yaml
    docker/nginx/sites/default.conf
    docker/php/php.ini
    docker/mysql/my.cnf
    src/config/config.php
    """.strip().split('\n')

    # 这里简化处理，实际需要读取 templates 目录渲染
    # 完整实现可参考 PHP 版的 renderConfigs
    deploy_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    template_dir = os.path.join(deploy_dir, 'template')

    for rel_path in imports:
        rel_path = rel_path.strip()
        template_file = os.path.join(template_dir, rel_path)
        if not os.path.exists(template_file):
            continue

        with open(template_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # 简单变量替换
        vars = {
            'APP_NAME': config.project_config.get('name', ''),
            'NGINX_PORT': str(nginx_port),
            'TZ': config.get_env('TZ', 'Asia/Shanghai'),
        }
        for k, v in vars.items():
            content = content.replace('{{' + k + '}}', v)

        remote_path = f"{project_path}/{rel_path}"
        ssh.upload_content(content, remote_path)
