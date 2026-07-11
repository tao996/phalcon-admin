"""
Phalcon Admin Deploy UI — Nginx 域名面板
"""
import tkinter as tk
from tkinter import ttk
from .base import PanelBase, ActionButton
from ..core import router as router_ops


class NginxPanel(PanelBase):
    """Nginx 域名管理：添加/移除域名、SSL"""

    def __init__(self, parent, app):
        super().__init__(parent, app)
        self._build_ui()

    def _build_ui(self):
        frame = ttk.LabelFrame(self, text='域名管理', padding=10)
        frame.pack(fill=tk.X, padx=10, pady=5)

        # 域名输入
        f = ttk.Frame(frame)
        f.pack(fill=tk.X)
        ttk.Label(f, text='域名:').pack(side=tk.LEFT)
        self.domain_var = tk.StringVar()
        ttk.Entry(f, textvariable=self.domain_var, width=30).pack(side=tk.LEFT, padx=5)

        btn_frame = ttk.Frame(frame)
        btn_frame.pack(fill=tk.X, pady=(5, 0))
        ActionButton(btn_frame, text='添加域名', command=self.add_domain).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame, text='移除域名', command=self.remove_domain).pack(side=tk.LEFT, padx=5)

        # SSL
        frame2 = ttk.LabelFrame(self, text='SSL 证书', padding=10)
        frame2.pack(fill=tk.X, padx=10, pady=5)

        f2 = ttk.Frame(frame2)
        f2.pack(fill=tk.X)
        ttk.Label(f2, text='邮箱:').pack(side=tk.LEFT)
        self.email_var = tk.StringVar()
        ttk.Entry(f2, textvariable=self.email_var, width=30).pack(side=tk.LEFT, padx=5)
        ActionButton(f2, text='申请 SSL', command=self.apply_ssl).pack(side=tk.LEFT, padx=5)

    def _ensure_project(self) -> bool:
        if not self.project_name:
            self.app.output.append('[错误] 请先选择项目')
            return False
        return True

    def add_domain(self):
        if not self._ensure_project():
            return
        domain = self.domain_var.get().strip()
        if not domain:
            self.app.output.append('[错误] 请输入域名')
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                mode = router_ops.detect_mode(self.ssh)
                nginx_port = int(self.config.project_config.get('nginxPort', 8071))
                results = router_ops.add_domain(
                    self.ssh, self.project_name, [domain], mode, nginx_port)
                for r in results:
                    self.app.output.append(r)
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def remove_domain(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                mode = router_ops.detect_mode(self.ssh)
                config_dir = '/etc/nginx/conf.d' if mode == router_ops.MODE_HOST else '/etc/nginx-router/conf.d'
                self.ssh.exec(f"rm -f {config_dir}/{self.project_name}.conf")
                router_ops.nginx_reload(self.ssh)
                self.app.output.append('域名已移除，Nginx 已重载')
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def apply_ssl(self):
        if not self._ensure_project():
            return
        email = self.email_var.get().strip()
        domain = self.domain_var.get().strip()
        if not email:
            self.app.output.append('[错误] 请输入邮箱')
            return
        if not domain:
            self.app.output.append('[错误] 请输入域名')
            return

        self.app.output.clear()
        project_path = self.config.project_path

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                webroot = f"{project_path}/src/public"

                self.app.output.append(f'申请证书: {domain}')
                self.ssh.exec(f"certbot certonly --webroot -w {webroot} -d {domain} "
                              f"--non-interactive --agree-tos -m {email}")
                self.app.output.append('证书已生成')

                # 软链
                self.ssh.exec(f"mkdir -p /etc/nginx/ssl")
                self.ssh.exec(
                    f"ln -sf /etc/letsencrypt/live/{domain}/fullchain.pem /etc/nginx/ssl/{domain}.pem && "
                    f"ln -sf /etc/letsencrypt/live/{domain}/privkey.pem /etc/nginx/ssl/{domain}.key")

                # 更新配置
                mode = router_ops.detect_mode(self.ssh)
                nginx_port = int(self.config.project_config.get('nginxPort', 8071))
                target = f"127.0.0.1:{nginx_port}"
                config = router_ops.generate_server_block([domain], target, ssl=True)

                config_dir = '/etc/nginx/conf.d' if mode == router_ops.MODE_HOST else '/etc/nginx-router/conf.d'
                self.ssh.upload_content(config, f"{config_dir}/{self.project_name}.conf")
                self.app.output.append('Nginx 配置已更新')

                router_ops.nginx_reload(self.ssh)
                self.app.output.append('SSL 配置完成')
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)
