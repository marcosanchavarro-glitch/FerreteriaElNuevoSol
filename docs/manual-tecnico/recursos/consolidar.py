"""Consolida las fuentes Markdown y Mermaid. Solo escribe documentación."""
from pathlib import Path
import re

base=Path(__file__).resolve().parents[1]
parts=[base/'README.md',*sorted(base.glob('[0-9][0-9]-*.md')),
       *[base/'anexos'/n for n in ['diccionario-datos.md','inventario.md','configuracion.md','glosario-referencias.md']]]
output=[]
for path in parts:
    content=path.read_text(encoding='utf-8')
    if path.name=='README.md':
        content=content.replace('# Manual Técnico y de Programación de Ferretería El Nuevo Sol','# Manual Técnico y de Programación de Ferretería El Nuevo Sol\n\nVersión consolidada de las fuentes en `docs/manual-tecnico/`.')
    def adjust(match):
        label,target=match.group(1),match.group(2)
        if re.match(r'https?://|#',target): return match.group(0)
        return f'[{label}](manual-tecnico/{path.relative_to(base).parent.as_posix()+"/" if path.parent!=base else ""}{target})'
    content=re.sub(r'\[([^\]]+)\]\(([^)]+)\)',adjust,content)
    if path.parent==base:
        for diagram in re.findall(r'\(manual-tecnico/(diagramas/[^)]+\.mmd)\)',content):
            content+='\n\n```mermaid\n'+(base/diagram).read_text(encoding='utf-8').rstrip()+'\n```\n'
    output.append(content.rstrip())
target=base.parent/'MANUAL-TECNICO-EL-NUEVO-SOL.md'
target.write_text('\n\n---\n\n'.join(output)+'\n',encoding='utf-8')
print(f'Consolidado: {len(parts)-5} capítulos; {target.stat().st_size} bytes.')
