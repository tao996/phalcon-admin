"""
Phalcon Admin Deploy UI — 服务器面板
"""
import tkinter as tk
from tkinter import ttk
from .base import PanelBase, ActionButton
from ..core import router as router_ops


class ServerPanel(PanelBase):
    """服务器管理：环境检测、Nginx 重载、Nginx 日志"""

    def __init__(self, parent, app):
        super().__init__(parent, app)
        self._build_ui()

    def _build_ui(self):
        # 环境检测
        frame = ttk.LabelFrame(self, text='环境检测', padding=10)
        frame.pack(fill=tk.X, padx=10, pady=5)

        btn_frame = ttk.Frame(frame)
        btn_frame.pack(fill=tk.X)
        ActionButton(btn_frame, text='检测环境', command=self.detect_env).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame, text='执行安装 (-y)', command=self.install_server).pack(side=tk.LEFT, padx=5)

        # Nginx 操作
        frame2 = ttk.LabelFrame(self, text='Nginx', padding=10)
        frame2.pack(fill=tk.X, padx=10, pady=5)

        btn_frame2 = ttk.Frame(frame2)
        btn_frame2.pack(fill=tk.X)
        ActionButton(btn_frame2, text='重载', command=self.reload_nginx).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame2, text='查看错误日志', command=self.view_error_log).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame2, text='查看访问日志', command=self.view_access_log).pack(side=tk.LEFT, padx=5)

    def detect_env(self):
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return

                ssh = self.ssh
                out = self.app.output

                out.append('━' * 40)
                out.append('服务器环境检测报告')
                out.append('━' * 40)

                # 操作系统
                _, sys_info, _ = ssh.exec(
                    r". /etc/os-release 2>/dev/null && echo \"$PRETTY_NAME\" || uname -a")
                out.append(f'系统:       {sys_info.strip()}')

                # Nginx 是否安装
                code, _, _ = ssh.exec("command -v nginx >/dev/null 2>&1")
                nginx_installed = code == 0
                nginx_status = '已安装' if nginx_installed else '未安装'

                # Nginx 是否运行
                run_code, _, _ = ssh.exec(
                    "ps aux 2>/dev/null | grep -v grep | grep -q ' nginx'")
                nginx_running = run_code == 0
                if nginx_running:
                    nginx_status += '  (运行中)'
                elif nginx_installed:
                    nginx_status += '  (未运行)'
                out.append(f'Nginx:      {nginx_status}')

                # Nginx 配置目录
                if nginx_installed:
                    _, config_dir, _ = ssh.exec(
                        "nginx -t 2>&1 | grep -o '/etc/nginx/[^ ]*' | head -1 || echo '/etc/nginx/conf.d'")
                    out.append(f'Nginx 配置: {config_dir.strip()}')

                # Certbot
                code, _, _ = ssh.exec("command -v certbot >/dev/null 2>&1")
                out.append(f'Certbot:    {'已安装' if code == 0 else '未安装'}')

                # 端口 80
                _, port80, _ = ssh.exec(
                    "ss -tln 2>/dev/null | grep -q '\\.80 ' && echo 'in_use' || "
                    "netstat -tln 2>/dev/null | grep -q '\\.80 ' && echo 'in_use' || echo 'free'")
                out.append(f'端口 80:    {'已被占用' if 'in_use' in port80 else '空闲'}')

                # 端口 443
                _, port443, _ = ssh.exec(
                    "ss -tln 2>/dev/null | grep -q '\\.443 ' && echo 'in_use' || "
                    "netstat -tln 2>/dev/null | grep -q '\\.443 ' && echo 'in_use' || echo 'free'")
                out.append(f'端口 443:   {'已被占用' if 'in_use' in port443 else '空闲'}')

                # Docker Router 容器
                _, router, _ = ssh.exec(
                    "docker inspect -f '{{.State.Running}}' phalcon-router 2>/dev/null || echo 'false'")
                out.append(f'Docker Router: {'已在运行' if router.strip() == 'true' else '未运行'}')

                # Docker
                code, _, _ = ssh.exec("command -v docker >/dev/null 2>&1")
                docker_installed = code == 0
                out.append(f'Docker:      {'已安装' if docker_installed else '未安装'}')

                if docker_installed:
                    # Docker Compose
                    _, compose_cmd, _ = ssh.exec(
                        "docker compose version >/dev/null 2>&1 && echo 'docker compose' || "
                        "docker-compose --version >/dev/null 2>&1 && echo 'docker-compose' || echo 'NO'")
                    cc = compose_cmd.strip()
                    out.append(f'Docker Compose: {'未安装' if cc == 'NO' else cc}')

                    # phalcon-shared 网络
                    _, net, _ = ssh.exec(
                        "docker network inspect phalcon-shared >/dev/null 2>&1 && echo 'YES' || echo 'NO'")
                    out.append(f'网络 phalcon-shared: {'已创建' if net.strip() == 'YES' else '未创建'}')

                # 推荐模式
                mode = router_ops.detect_mode(ssh)
                mode_label = '宿主机 Nginx' if mode == router_ops.MODE_HOST else 'Docker Router'
                out.append('')
                out.append(f'推荐模式:   {mode_label}')
                out.append('━' * 40)

            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def install_server(self):
        self.app.output.clear()
        self.app.output.append('执行服务器初始化...')

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                mode = router_ops.detect_mode(self.ssh)
                self.app.output.append(f'检测到模式: {mode}')
                # 创建共享网络
                self.ssh.exec("docker network create phalcon-shared 2>/dev/null || true")
                self.app.output.append('网络 phalcon-shared 已就绪')

                if mode == router_ops.MODE_HOST:
                    self.ssh.exec("mkdir -p /etc/nginx/conf.d")
                    self.ssh.exec("nginx -t")
                    self.app.output.append('宿主机 Nginx 配置正确')
                else:
                    self.app.output.append('Docker Router 模式待实现')

                self.app.output.append('初始化完成')
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def reload_nginx(self):
        def _run():
            if not self.app.ensure_ssh():
                return
            self.app.output.append('验证 Nginx 配置...')
            code, out, err = self.ssh.exec("nginx -t 2>&1 || docker exec phalcon-router nginx -t 2>&1")
            if code == 0:
                self.app.output.append('配置正确')
                router_ops.nginx_reload(self.ssh)
                self.app.output.append('Nginx 已重载')
            else:
                self.app.output.append(f'[错误] 配置验证失败:\n{out}\n{err}')

        self.app.output.clear()
        self.run_async(_run)

    def _view_log(self, log_type):
        def _run():
            if not self.app.ensure_ssh():
                return
            self.app.output.append(f'=== Nginx {log_type} 日志 ===')
            lines = router_ops.nginx_log(self.ssh, log_type)
            for line in lines:
                self.app.output.append(line)

        self.app.output.clear()
        self.run_async(_run)

    def view_error_log(self):
        self._view_log('error')

    def view_access_log(self):
        self._view_log('access')
