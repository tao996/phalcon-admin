"""
Phalcon Admin Deploy UI — Docker Compose 操作
"""
from .ssh import SSHClient
from ..core.config import DeployConfig


def get_compose_cmd(ssh: SSHClient) -> str:
    """获取可用的 compose 命令"""
    code, out, _ = ssh.exec("docker compose version >/dev/null 2>&1 && echo 'docker compose' || echo 'docker-compose'")
    cmd = out.strip()
    return cmd if cmd else 'docker-compose'


def get_compose_file(ssh: SSHClient, project_path: str) -> str:
    """检测可用的 compose 文件"""
    code, out, _ = ssh.exec(f"test -f {project_path}/docker-compose.ports.yaml && echo 'ports' || echo 'default'")
    return 'docker-compose.ports.yaml' if out.strip() == 'ports' else 'docker-compose.yaml'


def dc_restart(ssh: SSHClient, project_path: str, service: str = '') -> list[str]:
    """重启容器"""
    cmd = get_compose_cmd(ssh)
    cf = get_compose_file(ssh, project_path)
    svc = f' {service}' if service else ''
    full_cmd = f"cd {project_path} && {cmd} -f {cf} up -d{svc}" if not service else \
               f"cd {project_path} && {cmd} -f {cf} restart{svc}"
    if not service:
        full_cmd = f"cd {project_path} && {cmd} -f {cf} up -d"

    # 同步执行返回输出
    code, out, err = ssh.exec(full_cmd)
    lines = out.split('\n') if out else []
    if err:
        lines.append(f"[错误] {err}")
    return lines


def dc_status(ssh: SSHClient, project_path: str) -> list[str]:
    """查看容器状态"""
    cmd = get_compose_cmd(ssh)
    cf = get_compose_file(ssh, project_path)
    code, out, err = ssh.exec(f"cd {project_path} && {cmd} -f {cf} ps")
    lines = out.split('\n') if out else ['(无输出)']
    return lines


def dc_logs(ssh: SSHClient, project_path: str, service: str = '', lines: int = 50) -> list[str]:
    """查看容器日志"""
    cmd = get_compose_cmd(ssh)
    cf = get_compose_file(ssh, project_path)
    svc = f' {service}' if service else ''
    full_cmd = f"cd {project_path} && {cmd} -f {cf} logs --tail={lines}{svc}"
    code, out, err = ssh.exec(full_cmd)
    result = out.split('\n') if out else []
    if err:
        result.append(f"[错误] {err}")
    return result
