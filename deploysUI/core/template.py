"""
Phalcon Admin Deploy UI — 模板渲染
"""
import re
from typing import Optional


class TemplateRenderer:
    """简单的 {{VAR}} 模板渲染"""

    def __init__(self, template_dir: str = ''):
        self.template_dir = template_dir

    def render(self, template_path: str, vars: dict) -> str:
        """读取模板文件并替换 {{VAR}} 占位符"""
        with open(template_path, 'r', encoding='utf-8') as f:
            content = f.read()

        def replace_match(m):
            key = m.group(1)
            return str(vars.get(key, m.group(0)))

        return re.sub(r'\{\{(\w+)\}\}', replace_match, content)
