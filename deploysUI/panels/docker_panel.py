"""
Phalcon Admin Deploy UI — Docker 容器面板
"""
import tkinter as tk
from tkinter import ttk
from .base import PanelBase, ActionButton
from ..core import docker as docker_ops


class DockerPanel(PanelBase):
    """容器管理：状态、重启、日志"""

    def __init__(self, parent, app):
        super().__init__(parent, app)
        self._build_ui()

    def _build_ui(self):
        # 容器状态
        frame = ttk.LabelFrame(self, text='容器操作', padding=10)
        frame.pack(fill=tk.X, padx=10, pady=5)

        btn_frame = ttk.Frame(frame)
        btn_frame.pack(fill=tk.X)
        ActionButton(btn_frame, text='查看状态', command=self.show_status).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame, text='重启全部', command=self.restart_all).pack(side=tk.LEFT, padx=5)

        # 指定容器
        frame2 = ttk.LabelFrame(self, text='指定容器', padding=10)
        frame2.pack(fill=tk.X, padx=10, pady=5)

        f = ttk.Frame(frame2)
        f.pack(fill=tk.X)
        ttk.Label(f, text='容器名:').pack(side=tk.LEFT)
        self.service_var = tk.StringVar(value='php')
        ttk.Combobox(f, textvariable=self.service_var,
                     values=['php', 'nginx', 'mysql', 'redis'],
                     width=10).pack(side=tk.LEFT, padx=5)
        ActionButton(f, text='重启', command=self.restart_service).pack(side=tk.LEFT, padx=5)
        ActionButton(f, text='查看日志', command=self.show_logs).pack(side=tk.LEFT, padx=5)

        # 日志行数
        ttk.Label(f, text='行数:').pack(side=tk.LEFT, padx=(15, 0))
        self.lines_var = tk.StringVar(value='50')
        ttk.Entry(f, textvariable=self.lines_var, width=6).pack(side=tk.LEFT, padx=5)

    def _ensure_project(self) -> bool:
        if not self.project_name:
            self.app.output.append('[错误] 请先选择项目')
            return False
        return True

    def show_status(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            if not self.app.ensure_ssh():
                return
            lines = docker_ops.dc_status(self.ssh, self.config.project_path)
            for line in lines:
                self.app.output.append(line)

        self.run_async(_run)

    def restart_all(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            if not self.app.ensure_ssh():
                return
            self.app.output.append('重启全部容器...')
            lines = docker_ops.dc_restart(self.ssh, self.config.project_path)
            for line in lines:
                self.app.output.append(line)
            self.app.output.append('完成')

        self.run_async(_run)

    def restart_service(self):
        if not self._ensure_project():
            return
        self.app.output.clear()
        service = self.service_var.get().strip()

        def _run():
            if not self.app.ensure_ssh():
                return
            self.app.output.append(f'重启容器: {service}...')
            lines = docker_ops.dc_restart(self.ssh, self.config.project_path, service)
            for line in lines:
                self.app.output.append(line)

        self.run_async(_run)

    def show_logs(self):
        if not self._ensure_project():
            return
        self.app.output.clear()
        service = self.service_var.get().strip()
        try:
            lines_count = int(self.lines_var.get())
        except ValueError:
            lines_count = 50

        def _run():
            if not self.app.ensure_ssh():
                return
            label = f'容器日志: {service}' if service else '全部容器日志'
            self.app.output.append(f'=== {label} (最新{lines_count}行) ===')
            lines = docker_ops.dc_logs(self.ssh, self.config.project_path, service, lines_count)
            for line in lines:
                self.app.output.append(line)

        self.run_async(_run)
