"""
Phalcon Admin Deploy UI — Nginx Router 管理
"""

MODE_HOST = 'host_nginx'
MODE_DOCKER = 'docker_router'


def detect_mode(ssh) -> str:
    """检测 Router 模式"""
    # 检查 Docker Router
    code, out, _ = ssh.exec(
        "docker inspect -f '{{.State.Running}}' phalcon-router 2>/dev/null || echo 'false'"
    )
    if out.strip() == 'true':
        return MODE_DOCKER

    # 检查宿主机 nginx
    code, out, _ = ssh.exec("ps aux 2>/dev/null | grep -v grep | grep -q ' nginx' && echo 'YES' || echo 'NO'")
    if out.strip() == 'YES':
        return MODE_HOST

    return MODE_HOST  # 默认


def nginx_reload(ssh) -> list[str]:
    """重载 Nginx"""
    results = []
    cmd = ("docker exec phalcon-router nginx -s reload 2>/dev/null || "
           "nginx -s reload 2>/dev/null || "
           "systemctl reload nginx 2>/dev/null || echo 'RELOAD_FAILED'")
    code, out, err = ssh.exec(cmd)
    if 'RELOAD_FAILED' in out:
        results.append('[错误] Nginx 重载失败')
    else:
        results.append('Nginx 已重载')
    return results


def nginx_log(ssh, log_type: str = 'error', lines: int = 50) -> list[str]:
    """查看 Nginx 日志"""
    log_file = f"/var/log/nginx/{log_type}.log"
    # 先尝试 Docker Router
    code, out1, _ = ssh.exec(f"docker exec phalcon-router tail -n {lines} {log_file} 2>/dev/null || echo 'DOCKER_FAILED'")
    if 'DOCKER_FAILED' not in out1:
        return out1.split('\n') if out1 else []

    # 回退到宿主机路径
    code, out2, _ = ssh.exec(f"tail -n {lines} {log_file} 2>/dev/null || echo '日志文件不存在'")
    return out2.split('\n') if out2 else []


def generate_server_block(domains: list[str], target: str, ssl: bool = False) -> str:
    """生成 nginx server block 配置"""
    server_name = ' '.join(domains)
    primary = domains[0]

    if ssl:
        return f"""server {{
    listen 80;
    server_name {server_name};
    return 301 https://$server_name$request_uri;
}}

server {{
    listen 443 ssl http2;
    server_name {server_name};

    ssl_certificate     /etc/nginx/ssl/{primary}.pem;
    ssl_certificate_key /etc/nginx/ssl/{primary}.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    location / {{
        proxy_pass http://{target};
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }}
}}
"""
    else:
        return f"""server {{
    listen 80;
    server_name {server_name};

    location / {{
        proxy_pass http://{target};
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }}
}}
"""


def add_domain(ssh, project_name: str, domains: list[str],
               mode: str = MODE_HOST, nginx_port: int = 8071, ssl: bool = False) -> list[str]:
    """添加域名到 Router"""
    results = []

    # 确定目标地址
    if mode == MODE_DOCKER:
        target = f"{project_name}-nginx:80"
    else:
        target = f"127.0.0.1:{nginx_port}"

    # 确定配置目录
    config_dir = '/etc/nginx/conf.d' if mode == MODE_HOST else '/etc/nginx-router/conf.d'

    config = generate_server_block(domains, target, ssl)
    remote_file = f"{config_dir}/{project_name}.conf"

    results.append(f"域名: {', '.join(domains)} → {target}")
    ssh.exec(f"mkdir -p {config_dir}")
    ssh.upload_content(config, remote_file)
    results.append(f"配置已上传: {remote_file}")

    # 重载
    code, out, err = ssh.exec(nginx_reload_cmd(mode))
    results.append('Nginx 已重载')

    return results


def nginx_reload_cmd(mode: str = MODE_HOST) -> str:
    if mode == MODE_DOCKER:
        return "docker exec phalcon-router nginx -s reload 2>/dev/null || nginx -s reload"
    return "nginx -s reload 2>/dev/null || systemctl reload nginx 2>/dev/null"
