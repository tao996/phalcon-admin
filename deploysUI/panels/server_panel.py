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
        self.app.output.append('检测服务器环境...')

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                result = router_ops.detect_mode(self.ssh)
                self.app.output.append(f'模式: {result}')

                # 获取系统信息
                _, out, _ = self.ssh.exec("cat /etc/os-release 2>/dev/null | head -1 || uname -a")
                self.app.output.append(f'系统: {out.strip()}')

                # Nginx 状态
                _, out, _ = self.ssh.exec(
                    "ps aux 2>/dev/null | grep -v grep | grep -q ' nginx' && echo '运行中' || echo '未运行'")
                self.app.output.append(f'Nginx: {out.strip()}')

                self.app.output.append('检测完成')
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
