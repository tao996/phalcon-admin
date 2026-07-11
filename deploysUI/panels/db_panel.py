"""
Phalcon Admin Deploy UI — 数据库面板
"""
import tkinter as tk
from tkinter import ttk
from .base import PanelBase, ActionButton


class DbPanel(PanelBase):
    """数据库运维：SSH 隧道、phpMyAdmin"""

    def __init__(self, parent, app):
        super().__init__(parent, app)
        self._build_ui()

    def _build_ui(self):
        # SSH 隧道
        frame = ttk.LabelFrame(self, text='SSH 隧道', padding=10)
        frame.pack(fill=tk.X, padx=10, pady=5)

        f = ttk.Frame(frame)
        f.pack(fill=tk.X)
        ttk.Label(f, text='本地端口:').pack(side=tk.LEFT)
        self.local_port_var = tk.StringVar(value='13306')
        ttk.Entry(f, textvariable=self.local_port_var, width=8).pack(side=tk.LEFT, padx=5)
        ActionButton(f, text='启动隧道', command=self.start_tunnel).pack(side=tk.LEFT, padx=5)
        ttk.Label(f, text='需在终端执行，此处仅显示命令').pack(side=tk.LEFT, padx=10)

        # phpMyAdmin
        frame2 = ttk.LabelFrame(self, text='phpMyAdmin', padding=10)
        frame2.pack(fill=tk.X, padx=10, pady=5)

        f2 = ttk.Frame(frame2)
        f2.pack(fill=tk.X)
        ttk.Label(f2, text='端口:').pack(side=tk.LEFT)
        self.pma_port_var = tk.StringVar(value='13307')
        ttk.Entry(f2, textvariable=self.pma_port_var, width=8).pack(side=tk.LEFT, padx=5)
        ActionButton(f2, text='部署', command=self.deploy_pma).pack(side=tk.LEFT, padx=5)
        ActionButton(f2, text='清理', command=self.remove_pma).pack(side=tk.LEFT, padx=5)

    def _ensure_project(self) -> bool:
        if not self.project_name:
            self.app.output.append('[错误] 请先选择项目')
            return False
        return True

    def start_tunnel(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        try:
            local_port = int(self.local_port_var.get())
        except ValueError:
            self.app.output.append('[错误] 端口格式错误')
            return

        host = self.config.server_host
        user = self.config.server_user
        port = self.config.server_port

        cmd = f"ssh -L 127.0.0.1:{local_port}:yihe-mysql:3306 -N {user}@{host} -p {port}"
        self.app.output.append('=== SSH 隧道命令（在终端执行）===')
        self.app.output.append(cmd)
        self.app.output.append('')
        self.app.output.append('连接后 MySQL 连接信息:')
        self.app.output.append(f"  Host: 127.0.0.1")
        self.app.output.append(f"  Port: {local_port}")
        self.app.output.append(f"  User: {self.config.get_env('MYSQL_USER', 'admin')}")
        self.app.output.append(f"  按 Ctrl+C 关闭隧道")

    def deploy_pma(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        try:
            host_port = int(self.pma_port_var.get())
        except ValueError:
            self.app.output.append('[错误] 端口格式错误')
            return

        project_name = self.project_name
        db_user = self.config.get_env('MYSQL_USER', 'admin')
        db_pass = self.config.get_env('MYSQL_PASSWORD', '')

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                network = f"{project_name}_backend"
                container = f"{project_name}-pma"

                # 检查是否已在运行
                code, out, _ = self.ssh.exec(
                    f"docker inspect -f '{{{{.State.Running}}}}' {container} 2>/dev/null || echo 'not_found'")
                if out.strip() == 'true':
                    self.app.output.append('phpMyAdmin 已在运行')
                else:
                    # 清理旧的
                    self.ssh.exec(f"docker rm -f {container} 2>/dev/null || true")
                    # 启动
                    cmd = (
                        f"docker run -d --rm --name {container} --network {network} "
                        f"-p {host_port}:80 -e PMA_HOST=mysql -e PMA_PORT=3306 "
                        f"phpmyadmin/phpmyadmin"
                    )
                    self.ssh.exec(cmd)
                    self.app.output.append('phpMyAdmin 已部署')

                self.app.output.append(f"访问: http://{self.config.server_host}:{host_port}")
                self.app.output.append(f"用户名: {db_user}")
                self.app.output.append(f"密码:   {db_pass}")
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def remove_pma(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                container = f"{self.project_name}-pma"
                self.ssh.exec(f"docker rm -f {container} 2>/dev/null || echo '已清理'")
                self.app.output.append('phpMyAdmin 已清理')
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)
