"""
Phalcon Admin Deploy UI — 项目部署面板
"""
import tkinter as tk
from tkinter import ttk
from .base import PanelBase, ActionButton
from ..core import deployer, git as git_ops


class ProjectPanel(PanelBase):
    """项目管理：部署、更新、推送、重置"""

    def __init__(self, parent, app):
        super().__init__(parent, app)
        self._build_ui()

    def _build_ui(self):
        # 部署操作
        frame = ttk.LabelFrame(self, text='部署操作', padding=10)
        frame.pack(fill=tk.X, padx=10, pady=5)

        btn_frame = ttk.Frame(frame)
        btn_frame.pack(fill=tk.X)
        ActionButton(btn_frame, text='部署 (init -y)', command=self.deploy).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame, text='更新代码 (upgrade)', command=self.upgrade).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame, text='仅更新代码 (upgrade 无 -y)', command=self.upgrade_code).pack(side=tk.LEFT, padx=5)

        # 配置操作
        frame2 = ttk.LabelFrame(self, text='配置管理', padding=10)
        frame2.pack(fill=tk.X, padx=10, pady=5)

        btn_frame2 = ttk.Frame(frame2)
        btn_frame2.pack(fill=tk.X)
        ActionButton(btn_frame2, text='推送配置 (push)', command=self.push_config).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame2, text='推送脚本', command=self.push_scripts).pack(side=tk.LEFT, padx=5)
        ActionButton(btn_frame2, text='重置代码 (reset)', command=self.reset_code).pack(side=tk.LEFT, padx=5)

        # 项目信息
        self.info_frame = ttk.LabelFrame(self, text='项目信息', padding=10)
        self.info_frame.pack(fill=tk.X, padx=10, pady=5)
        self.info_text = tk.Text(self.info_frame, height=6, font=('Menlo', 9),
                                 bg='#f5f5f5', wrap=tk.WORD)
        self.info_text.pack(fill=tk.X)

    def refresh_info(self):
        """刷新项目信息"""
        self.info_text.delete('1.0', tk.END)
        if not self.config:
            self.info_text.insert(tk.END, '(请选择项目)')
            return

        cfg = self.config
        info = f"项目: {cfg.project_config.get('name', '-')}\n"
        info += f"路径: {cfg.project_path}\n"
        info += f"仓库: {cfg.project_config.get('repo', '-')}\n"
        info += f"分支: {cfg.project_config.get('branch', '-')}\n"
        info += f"域名: {', '.join(cfg.domains) if cfg.domains else '-'}\n"
        info += f"服务器: {cfg.server_host}:{cfg.server_port}"
        self.info_text.insert(tk.END, info)

    def _ensure_project(self) -> bool:
        if not self.project_name:
            self.app.output.append('[错误] 请先选择项目')
            return False
        return True

    def deploy(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                results = deployer.init_project(self.ssh, self.config, self.project_name)
                for r in results:
                    self.app.output.append(r)
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def upgrade(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                results = deployer.upgrade_project(self.ssh, self.config)
                for r in results:
                    self.app.output.append(r)
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def upgrade_code(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                path = self.config.project_path
                if not git_ops.git_check_exists(self.ssh, path):
                    self.app.output.append('[错误] 项目不存在')
                    return
                results = git_ops.git_pull(self.ssh, path)
                for r in results:
                    self.app.output.append(r)
                self.app.output.append('代码更新完成')
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def push_config(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                deploys_dir = self.app.deploys_dir
                results = deployer.push_configs(self.ssh, self.config, deploys_dir)
                for r in results:
                    self.app.output.append(r)
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def push_scripts(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                import os
                deploys_dir = self.app.deploys_dir
                scripts_dir = os.path.join(deploys_dir, 'scripts')
                remote_dir = f"{self.config.project_path}/deploys/scripts"

                self.ssh.exec(f"mkdir -p {remote_dir}")
                for fname in os.listdir(scripts_dir):
                    if fname.endswith('.sh'):
                        local_file = os.path.join(scripts_dir, fname)
                        with open(local_file, 'r') as f:
                            content = f.read()
                        remote_file = f"{remote_dir}/{fname}"
                        self.ssh.upload_content(content, remote_file)
                        self.ssh.exec(f"chmod +x {remote_file}")
                        self.app.output.append(f"已上传: {fname}")
                self.app.output.append('脚本推送完成')
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)

    def reset_code(self):
        if not self._ensure_project():
            return
        self.app.output.clear()

        def _run():
            try:
                if not self.app.ensure_ssh():
                    return
                results = deployer.reset_project(self.ssh, self.config)
                for r in results:
                    self.app.output.append(r)
            except Exception as e:
                self.app.output.append(f'[错误] {e}')

        self.run_async(_run)
