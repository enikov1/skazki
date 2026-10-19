"""Build self-contained, original SVG art to replace truncated demo files."""
from pathlib import Path
from html import escape
ROOT=Path(__file__).resolve().parents[1]
INK='#294c53'
def character(kind,outline=False):
 color='#fff' if outline else {'fox':'#e9a16e','cat':'#d3ac80','pig':'#ecb4ad','rabbit':'#d5ded9'}.get(kind,'#ba936e')
 if kind=='house':return f'<g stroke="{INK}" stroke-width="5" stroke-linejoin="round"><path d="M85 175h170v120H85Z" fill="'+('#fff' if outline else '#f2d39a')+'"/><path d="m63 175 107-90 107 90Z" fill="'+('#fff' if outline else '#e59c82')+'"/><rect x="145" y="224" width="45" height="71" rx="17" fill="white"/><rect x="105" y="193" width="28" height="28" rx="4" fill="white"/><rect x="210" y="193" width="28" height="28" rx="4" fill="white"/></g>'
 if kind=='flower':
  s=f'<path d="M170 200v100m0-40q-80-60-50-10 20 20 50 20m0 5q80-60 50-10-20 20-50 20" fill="none" stroke="{INK}" stroke-width="5"/>'
  for x,y in [(170,100),(215,135),(198,183),(142,183),(125,135)]:s+=f'<circle cx="{x}" cy="{y}" r="33" fill="'+('#fff' if outline else '#f3b8ac')+f'" stroke="{INK}" stroke-width="4"/>'
  return s+f'<circle cx="170" cy="145" r="29" fill="'+('#fff' if outline else '#f7d471')+f'" stroke="{INK}" stroke-width="4"/>'
 if kind=='turnip':return f'<g stroke="{INK}" stroke-width="5" stroke-linejoin="round"><path d="M170 150q-85-95-50-70 37 8 50 48 35-85 62-57-6 45-62 79Z" fill="'+('#fff' if outline else '#86b58b')+'"/><path d="M170 150c-105-37-100 100-18 126l18 32 18-32c82-26 87-163-18-126Z" fill="'+('#fff' if outline else '#f4d37b')+'"/></g>'
 if kind=='horse':return f'<g stroke="{INK}" stroke-width="5" stroke-linejoin="round"><path d="M117 280v-95l20-60-4-35 27 23 30-8 21 35 29 41q-7 24-35 21l-18-9-4 87Z" fill="'+('#fff' if outline else '#c9a27f')+f'"/><path d="m136 122-26 29-14 46 27-10" fill="none"/><circle cx="190" cy="147" r="4" fill="{INK}"/></g>'
 ears='<circle cx="119" cy="126" r="27"/><circle cx="221" cy="126" r="27"/>'
 if kind in ['cat','fox']:ears='<path d="m110 159 3-68 43 33m28 0 43-33 3 68"/>'
 if kind=='rabbit':ears='<ellipse cx="139" cy="100" rx="20" ry="60"/><ellipse cx="201" cy="100" rx="20" ry="60"/>'
 if kind=='sun':ears='';color='#fff' if outline else '#f6ce70'
 s=f'<g fill="{color}" stroke="{INK}" stroke-width="5" stroke-linejoin="round">{ears}<ellipse cx="170" cy="180" rx="78" ry="72"/><ellipse cx="170" cy="207" rx="38" ry="25" fill="'+('#fff' if outline else '#fff1d8')+'"/></g>'
 s+=f'<g fill="{INK}"><circle cx="142" cy="170" r="5"/><circle cx="198" cy="170" r="5"/><path d="m162 196 16 0-8 9Z"/></g><path d="M153 219q17 15 34 0" stroke="{INK}" stroke-width="4" stroke-linecap="round" fill="none"/>'
 if kind=='pig':s+=f'<ellipse cx="170" cy="200" rx="27" ry="18" fill="{color}" stroke="{INK}" stroke-width="4"/><circle cx="161" cy="200" r="4"/><circle cx="179" cy="200" r="4"/>'
 return s

def svg(content,title,w=340,h=430):return f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}" role="img"><title>{escape(title)}</title>{content}</svg>\n'
covers={'kolobok':('Колобок','sun'),'repka':('Репка','turnip'),'three-pigs':('Три поросёнка','pig'),'masha':('Маша и медведь','bear'),'sivka-burka':('Сивка-Бурка','horse'),'red-riding-hood':('Красная Шапочка','house'),'thumbelina':('Дюймовочка','flower'),'fox-rolling-pin':('Лиса и скалочка','fox'),'aybolit':('Айболит','rabbit'),'moidodyr':('Мойдодыр','sun'),'fox-and-crane':('Лиса и журавль','fox'),'koshkin-dom':('Кошкин дом','cat')}
replacements={}
for i,(name,(title,kind)) in enumerate(covers.items()):
 bg=['#fff0cd','#e7efdf','#f9e5de','#e1efea','#e8e9f5'][i%5]
 content=f'<rect width="340" height="430" rx="22" fill="{bg}"/><rect x="15" y="15" width="310" height="400" rx="14" fill="none" stroke="{INK}" stroke-opacity=".12" stroke-width="2"/><circle cx="280" cy="70" r="24" fill="white" opacity=".65"/><path d="M20 310q130-55 300 0v100H20Z" fill="{INK}" opacity=".05"/><text x="170" y="54" text-anchor="middle" font-family="sans-serif" font-size="12" letter-spacing="3" fill="{INK}">СКАЗКОМИР</text>'+character(kind)
 lines=['']
 for word in title.split():
  if len(lines[-1]+' '+word)>17:lines.append(word)
  else:lines[-1]=(lines[-1]+' '+word).strip()
 for j,line in enumerate(lines):content+=f'<text x="170" y="{350+27*j}" text-anchor="middle" font-family="sans-serif" font-weight="700" font-size="23" fill="{INK}">{escape(line)}</text>'
 (ROOT/f'src/assets/covers/{name}.svg').write_text(svg(content,title));replacements[f'covers/{name}.jpg']=f'covers/{name}.svg'
colorings={'cat-house':'cat','moydodyr':'sun','aybolit':'rabbit','masha':'bear','simple':'flower','repka':'turnip','three-pigs':'pig','turnip':'fox','thumbelina':'flower','little-red':'house','kolobok':'sun','fox-crane':'fox','sivka':'horse'}
for name,kind in colorings.items():
 content='<rect width="680" height="760" fill="white"/><g transform="translate(0 30) scale(2)">'+character(kind,True)+f'<path d="M35 315q35-17 65 0m140 0q35-17 65 0" stroke="{INK}" stroke-width="3" fill="none"/></g>'
 (ROOT/f'src/assets/colorings/{name}.svg').write_text(svg(content,'Контур для раскрашивания',680,760));replacements[f'colorings/{name}.png']=f'colorings/{name}.svg'
for i,name in enumerate(['audio-story','auth-register','coloring','find-differences','auth-login','logic-puzzles','story-workshop','family-reading']):
 bg=['#e4f0e7','#f7e9dc','#e8e9f6','#e7f1f2'][i%4]
 content=f'<rect width="640" height="440" rx="30" fill="{bg}"/><circle cx="520" cy="80" r="36" fill="#f5d38a"/><path d="M0 350q160-95 320 0t320 0v90H0Z" fill="#c3d9c8"/><g transform="translate(-15 55) scale(1.1)">'+character('bear')+'</g><g transform="translate(255 102) scale(.85)">'+character('rabbit')+f'</g><path d="M203 331q65-34 117 0 55-34 116 0v61q-57-25-116 0-58-25-117 0Z" fill="#fffaf0" stroke="{INK}" stroke-width="5"/><path d="M320 333v59" stroke="{INK}" stroke-width="4"/>'
 (ROOT/f'src/assets/illustrations/{name}.svg').write_text(svg(content,'Медвежонок и зайчонок читают вместе',640,440));replacements[f'illustrations/{name}.webp']=f'illustrations/{name}.svg'
for base in ['src','resources','app','database']:
 for p in (ROOT/base).rglob('*'):
  if p.suffix not in ['.pug','.js','.scss','.php','.json']:continue
  old=p.read_text();new=old
  for a,b in replacements.items():new=new.replace(a,b)
  if old!=new:p.write_text(new)
# Existing database records may still use the former raster URLs.
# Optional exports preserve those URLs without keeping truncated originals.
if '--raster-compat' in __import__('sys').argv:
 import io
 import cairosvg
 from PIL import Image
 for old,new in replacements.items():
  png=cairosvg.svg2png(url=str(ROOT/'src/assets'/new))
  image=Image.open(io.BytesIO(png)).convert('RGB')
  target=ROOT/'src/assets'/old
  image.save(target,quality=88,optimize=True)
print('Generated vector art; optional raster exports preserve existing database URLs.')
