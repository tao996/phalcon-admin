"""
Phalcon Admin Deploy UI — 共用 UI 组件
"""
import tkinter as tk
from tkinter import ttk, scrolledtext
import threading


class OutputConsole(ttk.Frame):
    """命令输出控制台"""

    def __init__(self, parent, height=12):
        super().__init__(parent)
        self.text = scrolledtext.ScrolledText(
            self, height=height, font=('Menlo', 10),
            bg='#1e1e1e', fg='#d4d4d4', insertbackground='white',
            wrap=tk.WORD, state=tk.DISABLED,
        )
        self.text.pack(fill=tk.BOTH, expand=True)

    def append(self, text: str):
        """追加文本"""
        self.text.config(state=tk.NORMAL)
        self.text.insert(tk.END, text + '\n')
        self.text.see(tk.END)
        self.text.config(state=tk.DISABLED)

    def clear(self):
        """清空"""
        self.text.config(state=tk.NORMAL)
        self.text.delete('1.0', tk.END)
        self.text.config(state=tk.DISABLED)


class ActionButton(ttk.Button):
    """带状态的按钮（执行时禁用）"""

    def __init__(self, parent, text, command, **kwargs):
        self._orig_command = command
        super().__init__(parent, text=text, command=self._wrapper, **kwargs)

    def _wrapper(self):
        if self.instate(['disabled']):
            return
        self.config(state=tk.DISABLED)
        try:
            self._orig_command()
        finally:
            self.config(state=tk.NORMAL)


class PanelBase(ttk.Frame):
    """面板基类"""

    def __init__(self, parent, app):
        super().__init__(parent)
        self.app = app  # 主应用引用

    def run_async(self, target, args=(), callback=None):
        """异步执行"""
        def _run():
            try:
                result = target(*args)
                if callback:
                    self.after(0, callback, result)
            except Exception as e:
                self.after(0, lambda: self.app.output.append(f'[错误] {e}'))

        t = threading.Thread(target=_run, daemon=True)
        t.start()
        return t

    @property
    def ssh(self):
        return self.app.ssh

    @property
    def config(self):
        return self.app.config

    @property
    def project_name(self):
        return self.app.current_project
