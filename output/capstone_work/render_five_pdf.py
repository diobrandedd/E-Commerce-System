from pathlib import Path
import sys,os,importlib.util,shutil
W=Path(__file__).parent
path=Path('C:/Users/Admin/.codex/plugins/cache/openai-primary-runtime/documents/26.904.11930/skills/documents/render_docx.py')
s=importlib.util.spec_from_file_location('packaged_renderer',path);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
# Word-exported PDF substitutes only for unavailable LibreOffice conversion.
m.convert_to_pdf=lambda *args,**kwargs:(str(W/'five.pdf'),'Rendered by Microsoft Word; LibreOffice is not installed.')
os.environ['PATH']='C:/Users/Admin/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin;'+os.environ['PATH']
paths=m.rasterize(str(W.parent/'documents/Pyx_Tracker_Capstone_Five_Features_Final.docx'),str(W/'five_render'),110,False,False)
print('Rendered',len(paths),'pages with packaged renderer.')


