"""
Phalcon Admin Deploy UI — Git 操作（远程执行）
"""


def git_pull(ssh, project_path: str) -> list[str]:
    """拉取代码"""
    code, out, err = ssh.exec(f"cd {project_path} && git pull")
    return out.split('\n') if out else []


def git_reset(ssh, project_path: str) -> list[str]:
    """重置代码"""
    code, out, err = ssh.exec(f"cd {project_path} && git reset --hard")
    return out.split('\n') if out else []


def git_check_exists(ssh, project_path: str) -> bool:
    """检查项目是否存在"""
    code, out, _ = ssh.exec(f"test -d {project_path}/.git && echo 'YES' || echo 'NO'")
    return out.strip() == 'YES'
