from pathlib import Path
from docx import Document
from docx.shared import Inches
import math
exec((Path(__file__).parent/'diagrams.py').read_text(encoding='utf-8-sig').split('# IPO')[0])
A=W/'five_assets'
c=Chart('use_feature_ovals',2820);c.im=Image.new('RGB',(3000,2820),'white');c.d=ImageDraw.Draw(c.im)
c.d.rectangle((300,20,2700,2770),outline='black',width=4)
c.text((340,60),'PYX TRACKER',52,True,2320,'center')
c.text((340,135),'Web-based Enterprise Operations Management System',37,True,2320,'center')
def actor(x,y,label):
 c.d.ellipse((x-35,y-135,x+35,y-65),outline='black',width=4);c.line([(x,y-65),(x,y+90)]);c.line([(x-75,y),(x+75,y)]);c.line([(x,y+90),(x-60,y+190)]);c.line([(x,y+90),(x+60,y+190)]);c.text((x-135,y+225),label,37,True,270,'center')
def oval(x,y,rx,ry,label,size=37,b=False):
 c.d.ellipse((x-rx,y-ry,x+rx,y+ry),outline='#555',width=4);lines=wrap(label,rx*2-45,font(size,b));c.text((x-rx+22,y-len(lines)*size*.625),label,size,b,rx*2-44,'center')
def point(x,y,rx,ry,tx,ty):
 dx,dy=tx-x,ty-y;k=1/math.sqrt((dx/rx)**2+(dy/ry)**2);return(x+dx*k,y+dy*k)
actor(135,1150,'Admin/\nExecutive');actor(2865,1420,'Staff')
groups=[(True,900,'Product Costing\nand Pricing',['Maintain formulas','Calculate costs','Define parcel recipes','Maintain prices']),
 (False,490,'Integrated Inventory\nManagement',['Receive materials','Track production','Confirm finished stock','Review movements']),
 (False,1100,'Multi-Platform Orders\nand Waybills',['Prepare order files','Validate order data','Stamp waybills','Finalize batches']),
 (False,1710,'Parcel Processing\nand Inventory Sync',['Record packing scans','Confirm movements','Save shipment photos','Review parcel history']),
 (False,2320,'Return-to-Sender\nManagement',['Record return case','Classify condition','Review stock-in','Confirm sellable stock'])]
for left,y,title,subs in groups:
 mx,sx=(610,1230) if left else (2390,1770)
 oval(mx,y,250,135,title,39,True)
 c.line([(210,1150),(mx-250,y)] if left else [(2790,1420),(mx+250,y)])
 for j,label in enumerate(subs):
  sy=y-210+j*140
  oval(sx,sy,250,60,label,35)
  a=point(mx,y,250,135,sx,sy);b=point(sx,sy,250,60,mx,y)
  c.line([a,b],True,True)
  tx=(a[0]+b[0])/2-56;ty=(a[1]+b[1])/2-35
  c.d.rectangle((tx-3,ty-2,tx+115,ty+34),fill='white')
  c.text((tx,ty),'«include»',24,w=112,align='center')
c.text((390,1590),'Main feature',36,True,550,'center')
c.text((1010,1590),'FDD subfeatures',36,True,550,'center')
c.line([(920,1615),(990,1615)],True,True)
c.text((405,1770),'The five main-feature ovals connect\nto the twenty subfeatures named in the FDD.',35,w=1090,align='center')
c.save()
src=W.parent/'documents/Pyx_Tracker_Capstone_FDD_Aligned.docx';d=Document(src)
old=(A/'use_fdd_aligned.png').read_bytes();new=(A/'use_feature_ovals.png').read_bytes();count=0
for rel in d.part.rels.values():
 if 'image' in rel.reltype and rel.target_part.blob==old:rel.target_part._blob=new;count+=1
assert count==1
for s in d.inline_shapes:
 rid=s._inline.graphic.graphicData.pic.blipFill.blip.embed
 if d.part.related_parts[rid].blob==new:s.width=Inches(6);s.height=Inches(5.64)
for p in d.paragraphs:
 if p.text.startswith('The use case diagram follows the Functional Decomposition Diagram directly.'):
  p.text='The use case diagram follows the five features and twenty subfeatures in the Functional Decomposition Diagram. Main features appear in the first column of ovals beside each actor, and their subfeatures appear in the adjacent column. Solid lines connect Admin/Executive or Staff to the main features. Dashed arrows labeled «include» connect each main feature to its four FDD subfeatures. The diagram presents the supported functions at feature level; it does not imply that every subfeature runs during every individual transaction. Admin/Executive maintains costing and pricing, while Staff performs the operational inventory, order, parcel, and return functions. No additional optional function is introduced through an «extend» relationship.'
out=src.with_name('Pyx_Tracker_Capstone_Feature_Subfeature_Use_Case.docx');d.save(out);print(out)
