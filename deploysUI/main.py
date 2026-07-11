#!/usr/bin/env python3
"""
Phalcon Admin Deploy UI — 主窗口
"""
import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import os
import sys

# 加入项目根到路径
APP_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(APP_DIR)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

from deploysUI.core.config import DeployConfig
from deploysUI.core.ssh import SSHClient
from deploysUI.panels.server_panel import ServerPanel
from deploysUI.panels.project_panel import ProjectPanel
from deploysUI.panels.docker_panel import DockerPanel
from deploysUI.panels.nginx_panel import NginxPanel
from deploysUI.panels.db_panel import DbPanel
from deploysUI.panels.base import OutputConsole


class DeployUI:
    """主应用"""

    def __init__(self):
        self.root = tk.Tk()
        self.root.title('Phalcon Admin 部署工具')
        self.root.geometry('1000x700')
        self.root.minsize(800, 600)

        # 状态
        self.config: DeployConfig = None
        self.ssh: SSHClient = None
        self.deploys_dir: str = ''
        self.current_project: str = ''
        self._ssh_connected = False

        self._build_menu()
        self._build_ui()

    def _build_menu(self):
        """菜单栏"""
        menubar = tk.Menu(self.root)
        self.root.config(menu=menubar)

        file_menu = tk.Menu(menubar, tearoff=0)
        menubar.add_cascade(label='文件', menu=file_menu)
        file_menu.add_command(label='加载配置...', command=self.load_config_dialog)
        file_menu.add_separator()
        file_menu.add_command(label='退出', command=self.root.quit)

        ssh_menu = tk.Menu(menubar, tearoff=0)
        menubar.add_cascade(label='SSH', menu=ssh_menu)
        ssh_menu.add_command(label='连接', command=self._connect_ssh)
        ssh_menu.add_command(label='断开', command=self._disconnect_ssh)

        about_menu = tk.Menu(menubar, tearoff=0)
        menubar.add_cascade(label='帮助', menu=about_menu)
        about_menu.add_command(label='关于', command=self.show_about)

    def _build_ui(self):
        """主界面布局"""
        # 顶部：状态栏
        self.status_frame = ttk.Frame(self.root)
        self.status_frame.pack(fill=tk.X, padx=10, pady=(10, 0))

        self.status_var = tk.StringVar(value='未加载配置')
        ttk.Button(self.status_frame, text='加载配置',
                   command=self.load_config_dialog).pack(side=tk.LEFT, padx=(0, 5))
        ttk.Label(self.status_frame, textvariable=self.status_var,
                  font=('', 10)).pack(side=tk.LEFT)

        self.ssh_status_var = tk.StringVar(value='SSH: 未连接')
        self.ssh_status_label = ttk.Label(
            self.status_frame, textvariable=self.ssh_status_var,
            font=('', 10), foreground='red')
        self.ssh_status_label.pack(side=tk.RIGHT)

        # 主区域：左侧项目列表 + 右侧标签页
        main_frame = ttk.Frame(self.root)
        main_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        # 左侧：项目列表
        left_frame = ttk.LabelFrame(main_frame, text='项目', width=200)
        left_frame.pack(side=tk.LEFT, fill=tk.Y, padx=(0, 5))
        left_frame.pack_propagate(False)

        self.project_listbox = tk.Listbox(left_frame, font=('Menlo', 11))
        self.project_listbox.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        self.project_listbox.bind('<<ListboxSelect>>', self._on_project_select)

        # 右侧：标签页
        right_frame = ttk.Frame(main_frame)
        right_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True)

        self.notebook = ttk.Notebook(right_frame)
        self.notebook.pack(fill=tk.BOTH, expand=True)

        # 各个面板
        self.server_panel = ServerPanel(self.notebook, self)
        self.project_panel = ProjectPanel(self.notebook, self)
        self.docker_panel = DockerPanel(self.notebook, self)
        self.nginx_panel = NginxPanel(self.notebook, self)
        self.db_panel = DbPanel(self.notebook, self)

        self.notebook.add(self.server_panel, text='服务器')
        self.notebook.add(self.project_panel, text='项目')
        self.notebook.add(self.docker_panel, text='Docker')
        self.notebook.add(self.nginx_panel, text='Nginx')
        self.notebook.add(self.db_panel, text='数据库')

        # 底部：输出控制台
        console_frame = ttk.LabelFrame(self.root, text='输出')
        console_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=(0, 10))

        console_toolbar = ttk.Frame(console_frame)
        console_toolbar.pack(fill=tk.X)
        ttk.Button(console_toolbar, text='清除输出',
                   command=self._clear_output).pack(side=tk.RIGHT, padx=5, pady=2)

        self.output = OutputConsole(console_frame, height=10)
        self.output.pack(fill=tk.BOTH, expand=True)
        self._show_welcome()

    def _clear_output(self):
        self.output.clear()

    def _show_welcome(self):
        self.output.append('┌─────────────────────────────────────┐')
        self.output.append('│  欢迎使用 Phalcon Admin 部署工具     │')
        self.output.append('│                                     │')
        self.output.append('│  首次使用：文件 → 加载配置           │')
        self.output.append('│  选择 deploys/.cache/config.json    │')
        self.output.append('│                                     │')
        self.output.append('│  生成配置：                          │')
        self.output.append('│  php deploy config:export yihe      │')
        self.output.append('│    --save=deploys/.cache/config.json │')
        self.output.append('└─────────────────────────────────────┘')

    def load_config_dialog(self):
        """选择 JSON 配置文件"""
        path = filedialog.askopenfilename(
            title='选择配置文件',
            filetypes=[('JSON 文件', '*.json'), ('所有文件', '*.*')],
            initialdir=os.path.join(PROJECT_ROOT, 'deploys', '.cache'),
        )
        if not path:
            return

        try:
            self.config = DeployConfig()
            msg = self.config.load_json(path)
            self.status_var.set(msg)
            self.deploys_dir = self.config.deploys_dir

            # 扫描项目
            projects = self.config.discover_projects()
            self.project_listbox.delete(0, tk.END)
            for p in projects:
                self.project_listbox.insert(tk.END, p['name'])
            self.output.append(f'发现 {len(projects)} 个项目')

            if not self._ssh_connected:
                self._connect_ssh()
        except Exception as e:
            messagebox.showerror('错误', str(e))

    def _on_project_select(self, event):
        """选择项目"""
        sel = self.project_listbox.curselection()
        if sel:
            name = self.project_listbox.get(sel[0])
            self.current_project = name
            self.output.append(f'选择项目: {name}')

            # 重新加载项目配置
            if self.config and self.deploys_dir:
                import json, os
                cache_file = os.path.join(self.deploys_dir, '.cache', 'server.json')
                if os.path.exists(cache_file):
                    with open(cache_file) as f:
                        self.config.data = json.load(f)
                    self.config.json_path = cache_file
                    # 尝试加载项目级缓存
                    project_cache = os.path.join(
                        self.deploys_dir, 'projects', name, '.cache.json')
                    if os.path.exists(project_cache):
                        with open(project_cache) as f:
                            self.config.data.update(json.load(f))

            self.project_panel.refresh_info()

    def ensure_ssh(self) -> bool:
        """确保 SSH 已连接"""
        if self._ssh_connected and self.ssh:
            return True
        return self._connect_ssh()

    def _connect_ssh(self) -> bool:
        """连接 SSH"""
        if not self.config:
            self.output.append('[错误] 请先加载配置')
            return False

        try:
            self.ssh = self.config.create_ssh_client()
            msg = self.ssh.connect()
            self._ssh_connected = True
            self.ssh_status_var.set(f'SSH: 已连接 {self.config.server_host}')
            self.ssh_status_label.config(foreground='green')
            self.output.append(msg)
            return True
        except Exception as e:
            self._ssh_connected = False
            self.ssh_status_var.set('SSH: 未连接')
            self.ssh_status_label.config(foreground='red')
            self.output.append(f'[错误] SSH 连接失败: {e}')
            return False

    def _disconnect_ssh(self):
        """断开 SSH"""
        if self.ssh:
            try:
                self.ssh.disconnect()
            except Exception:
                pass
        self.ssh = None
        self._ssh_connected = False
        self.ssh_status_var.set('SSH: 未连接')
        self.ssh_status_label.config(foreground='red')
        self.output.append('SSH 已断开')

    def show_about(self):
        messagebox.showinfo('关于', 'Phalcon Admin 部署工具\n'
                                   '版本 2.0\n\n'
                                   '基于 Python + Tkinter\n'
                                   '配置来源: php deploy config:export')

    def run(self):
        self.root.mainloop()


if __name__ == '__main__':
    app = DeployUI()
    app.run()
