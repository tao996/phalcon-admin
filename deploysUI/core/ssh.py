"""
Phalcon Admin Deploy UI — SSH 连接核心
"""
import paramiko
import threading
from typing import Optional, Callable


class SSHClient:
    """SSH 连接管理"""

    def __init__(self, host: str, port: int = 22, user: str = 'root',
                 password: str = '', key_file: str = '', timeout: int = 10):
        self.host = host
        self.port = port
        self.user = user
        self.password = password
        self.key_file = key_file
        self.timeout = timeout
        self._client: Optional[paramiko.SSHClient] = None
        self._sftp: Optional[paramiko.SFTPClient] = None

    def connect(self) -> str:
        """连接服务器，返回连接状态信息"""
        self._client = paramiko.SSHClient()
        self._client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

        kwargs = {
            'hostname': self.host,
            'port': self.port,
            'username': self.user,
            'timeout': self.timeout,
        }
        if self.password:
            kwargs['password'] = self.password
        if self.key_file:
            kwargs['key_filename'] = self.key_file

        try:
            self._client.connect(**kwargs)
            self._sftp = self._client.open_sftp()
            return f"已连接 {self.user}@{self.host}:{self.port}"
        except Exception as e:
            self._client = None
            raise ConnectionError(f"连接失败: {e}")

    def disconnect(self):
        """断开连接"""
        if self._sftp:
            try:
                self._sftp.close()
            except Exception:
                pass
            self._sftp = None
        if self._client:
            try:
                self._client.close()
            except Exception:
                pass
            self._client = None

    @property
    def connected(self) -> bool:
        return self._client is not None

    def exec(self, command: str, timeout: int = 30) -> tuple[int, str, str]:
        """
        执行远程命令
        返回 (returncode, stdout, stderr)
        """
        if not self._client:
            raise ConnectionError("SSH 未连接")

        try:
            stdin, stdout, stderr = self._client.exec_command(command, timeout=timeout)
            exit_code = stdout.channel.recv_exit_status()
            out = stdout.read().decode('utf-8', errors='replace').strip()
            err = stderr.read().decode('utf-8', errors='replace').strip()
            return exit_code, out, err
        except Exception as e:
            return -1, '', str(e)

    def upload_content(self, content: str, remote_path: str):
        """上传文本内容到远程文件"""
        if not self._sftp:
            raise ConnectionError("SFTP 未连接")
        # 确保目录存在
        remote_dir = remote_path.rsplit('/', 1)[0]
        try:
            self._sftp.stat(remote_dir)
        except FileNotFoundError:
            self._mkdir_p(remote_dir)

        with self._sftp.open(remote_path, 'w') as f:
            f.write(content)

    def download(self, remote_path: str, local_path: str):
        """下载远程文件到本地"""
        if not self._sftp:
            raise ConnectionError("SFTP 未连接")
        self._sftp.get(remote_path, local_path)

    def _mkdir_p(self, remote_dir: str):
        """递归创建远程目录"""
        dirs = remote_dir.split('/')
        path = ''
        for d in dirs:
            if not d:
                continue
            path += '/' + d
            try:
                self._sftp.stat(path)
            except FileNotFoundError:
                self._sftp.mkdir(path)

    def exec_async(self, command: str, callback: Callable[[str], None],
                   timeout: int = 60) -> threading.Thread:
        """
        异步执行命令，通过 callback 返回实时输出
        返回 threading.Thread
        """
        def _run():
            if not self._client:
                callback("[错误] SSH 未连接")
                return
            try:
                stdin, stdout, stderr = self._client.exec_command(
                    command, timeout=timeout, bufsize=1)
                # 实时读取 stdout
                for line in iter(stdout.readline, ''):
                    if line:
                        callback(line.rstrip())
                exit_code = stdout.channel.recv_exit_status()
                if exit_code != 0:
                    err = stderr.read().decode('utf-8', errors='replace').strip()
                    if err:
                        callback(f"[错误] {err}")
            except Exception as e:
                callback(f"[异常] {e}")

        t = threading.Thread(target=_run, daemon=True)
        t.start()
        return t
