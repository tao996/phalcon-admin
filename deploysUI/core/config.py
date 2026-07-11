"""
Phalcon Admin Deploy UI — 配置加载
从 JSON 配置文件中读取服务器和项目信息
"""
import json
import os
from typing import Optional


class DeployConfig:
    """加载和管理部署配置"""

    def __init__(self, json_path: str = ''):
        self.json_path = json_path
        self.data: dict = {}
        self.projects_dir: str = ''
        self.server_file: str = ''
        self.projects: list[dict] = []

    def load_json(self, json_path: str) -> str:
        """加载 JSON 配置文件，返回状态信息"""
        self.json_path = json_path
        if not os.path.exists(json_path):
            raise FileNotFoundError(f"文件不存在: {json_path}")

        with open(json_path, 'r', encoding='utf-8') as f:
            self.data = json.load(f)

        # 推测 deploys 目录和 projects 目录
        self._infer_paths()

        return f"已加载: {os.path.basename(json_path)}"

    def _infer_paths(self):
        """从 JSON 文件路径推测目录结构"""
        json_dir = os.path.dirname(os.path.abspath(self.json_path))

        # 如果 JSON 在 .cache 下，上一级是 deploys
        if json_dir.endswith('.cache'):
            self.deploys_dir = os.path.dirname(json_dir)
        else:
            # 否则以 JSON 所在目录为 deploys
            self.deploys_dir = json_dir

        self.projects_dir = os.path.join(self.deploys_dir, 'projects')

    def discover_projects(self) -> list[dict]:
        """扫描 deploys/projects/ 下的项目"""
        projects = []
        if not os.path.isdir(self.projects_dir):
            return projects

        for name in sorted(os.listdir(self.projects_dir)):
            project_dir = os.path.join(self.projects_dir, name)
            server_file = os.path.join(project_dir, 'server.php')
            if os.path.isdir(project_dir) and os.path.exists(server_file):
                projects.append({
                    'name': name,
                    'path': project_dir,
                    'server_file': server_file,
                })

        self.projects = projects
        return projects

    @property
    def ssh_config(self) -> dict:
        return self.data.get('ssh', {})

    @property
    def server_host(self) -> str:
        return self.ssh_config.get('host', '')

    @property
    def server_port(self) -> int:
        return self.ssh_config.get('port', 22)

    @property
    def server_user(self) -> str:
        return self.ssh_config.get('user', 'root')

    @property
    def server_password(self) -> str:
        return self.ssh_config.get('password', '')

    @property
    def server_key_file(self) -> str:
        return self.ssh_config.get('keyFile', '')

    @property
    def project_config(self) -> dict:
        """合并后的项目配置"""
        return self.data.get('project', {})

    @property
    def env(self) -> dict:
        return self.data.get('env', {})

    @property
    def config_overrides(self) -> dict:
        return self.data.get('config', {})

    @property
    def domains(self) -> list:
        return self.data.get('domains', [])

    @property
    def project_path(self) -> str:
        return self.project_config.get('path', '')

    @property
    def docker_images(self) -> dict:
        return self.data.get('docker', {}).get('images', {})

    def get_env(self, key: str, default='') -> str:
        return self.env.get(key, default)

    def get_config(self, key: str, default=''):
        """获取点号分隔的配置值，如 'app.title'"""
        keys = key.split('.')
        val = self.config_overrides
        for k in keys:
            if isinstance(val, dict):
                val = val.get(k, default)
            else:
                return default
        return val

    def create_ssh_client(self):
        """根据配置创建 SSH 客户端"""
        from .ssh import SSHClient
        return SSHClient(
            host=self.server_host,
            port=self.server_port,
            user=self.server_user,
            password=self.server_password,
            key_file=self.server_key_file,
        )
