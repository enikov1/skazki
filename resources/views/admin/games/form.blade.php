@extends('layouts.app')
@section('title', ($game->exists ? 'Редактировать игру' : 'Новая игра').' — Панель управления')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><a href="{{ route('admin.games.index') }}">Игры</a><span>/</span><span>{{ $game->exists ? 'Редактирование' : 'Новая игра' }}</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Конструктор</p><h1>{{ $game->exists ? 'Редактировать игру' : 'Создать игру' }}</h1><p class="lead">Заполните поля вручную или вставьте JSON, подготовленный внешним ИИ. Никакие данные не отправляются в ИИ с сайта.</p></div></div>
<form method="post" action="{{ $game->exists ? route('admin.games.update', $game) : route('admin.games.store') }}" data-ajax-form class="admin-game-form">@csrf @if($game->exists)@method('put')@endif
<div class="admin-panel form-grid">
<label class="field"><span>Тип игры</span><select name="type" required data-game-type><option value="differences" @selected(old('type', $game->type ?: 'differences') === 'differences')>Найди отличия</option><option value="mosaics" @selected(old('type', $game->type) === 'mosaics')>Мозаика</option></select></label>
<label class="field"><span>Название</span><input name="title" value="{{ old('title', $game->title) }}" maxlength="180" required placeholder="Например, Лесная прогулка"></label>
<label class="field"><span>Адрес (создастся из названия)</span><input name="slug" value="{{ old('slug', $game->slug) }}" maxlength="190" placeholder="lesnaya-progulka"></label>
<label class="field full"><span>Короткое описание</span><textarea name="description" rows="2" maxlength="1500">{{ old('description', $game->description) }}</textarea></label>
<label class="field"><span>Изображение / исходная картинка</span><input id="gameImageA" name="image_a" value="{{ old('image_a', $game->image_a) }}" maxlength="255" required placeholder="storage/media/image.jpg"><a href="{{ route('admin.media.index', ['select' => 'gameImageA']) }}" data-media-picker class="text-link">Выбрать в медиатеке</a></label>
<label class="field" data-difference-only><span>Вторая картинка (найди отличия)</span><input id="gameImageB" name="image_b" value="{{ old('image_b', $game->image_b) }}" maxlength="255" placeholder="storage/media/image-2.jpg"><a href="{{ route('admin.media.index', ['select' => 'gameImageB']) }}" data-media-picker class="text-link">Выбрать в медиатеке</a><small>Загрузите две картинки одинакового размера и с одинаковым кадрированием.</small></label>
<label class="field field-permission full"><input type="hidden" name="is_published" value="0"><span><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $game->is_published))> Опубликовать игру</span></label>
</div>
<section class="admin-panel ai-json-panel"><div class="section-heading"><div><p class="eyebrow">Данные конструктора</p><h2>Параметры игры в JSON</h2></div><button type="button" class="button button-soft button-small" data-load-example>Вставить пример</button></div>
<p class="form-hint" data-json-help>Укажите нормированные координаты отличий: x и y от 0 до 1, отсчёт от левого верхнего угла. На каждую отметку ИИ должен вернуть координаты для картинки.</p>
<label class="field"><span>JSON</span><textarea name="payload_json" rows="15" required spellcheck="false" data-json-input>{{ old('payload_json', json_encode($game->data ?: ['differences' => [['x'=>0.22,'y'=>0.35,'radius'=>0.055,'label'=>'Пример отличия']]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) }}</textarea></label>
<div class="game-payload-builder" data-difference-builder hidden>
<h3>Точки отличий</h3><p class="form-hint">Координаты указаны долями от размера изображения: 0 — левый/верхний край, 1 — правый/нижний.</p>
<div class="game-points-list" data-points-list></div><button type="button" class="button button-soft button-small" data-add-point>Добавить отличие</button>
</div>
<div class="game-payload-builder" data-mosaic-builder hidden><h3>Размер мозаики</h3><div class="form-grid"><label class="field"><span>Строки</span><input type="number" min="2" max="8" value="3" data-mosaic-rows></label><label class="field"><span>Столбцы</span><input type="number" min="2" max="8" value="3" data-mosaic-columns></label></div></div>
<p class="form-hint">Сценарий: подготовьте пару картинок и попросите мультимодальную ИИ-модель выдать JSON по образцу. Вставьте ответ в поле выше: отличия появятся в редактируемом списке. Проверьте точки и протестируйте игру до публикации.</p>
<div class="tool-row"><button type="button" class="button button-quiet" data-copy-prompt>Скопировать промпт для ИИ</button><span class="form-hint" data-copy-status></span></div>
</section>
<div class="tool-row"><button class="button" type="submit">Сохранить игру</button><a class="button button-quiet" href="{{ route('admin.games.index') }}">К списку игр</a>@if($game->exists)<button class="button button-danger" type="submit" form="delete-game">Удалить</button>@endif</div>
</form>
@if($game->exists)<form id="delete-game" method="post" action="{{ route('admin.games.destroy', $game) }}" data-ajax-form>@csrf @method('delete')</form>@endif
<script>
(() => {
const type = document.querySelector('[data-game-type]'), area = document.querySelector('[data-json-input]');
const samples = { differences: {differences:[{x:0.22,y:0.35,radius:0.055,label:'Красный шар'}]}, mosaics: {rows:3,columns:3,shuffle:true} };
const examples = document.querySelector('[data-load-example]'), diffBuilder=document.querySelector('[data-difference-builder]'), mosaicBuilder=document.querySelector('[data-mosaic-builder]');
function current(){try{return JSON.parse(area.value)}catch{return {}}}
function write(value){area.value=JSON.stringify(value,null,2)}
function update(){
 const diff=type.value==='differences', existing=current();
 if(diff&&!Array.isArray(existing.differences))write(samples.differences);
 if(!diff&&(!existing.rows||!existing.columns))write(samples.mosaics);
 document.querySelectorAll('[data-difference-only]').forEach(el=>el.hidden=!diff);
 document.querySelector('[name=image_b]').required=diff;
 diffBuilder.hidden=!diff; mosaicBuilder.hidden=diff;
 document.querySelector('[data-json-help]').textContent=diff?'Укажите нормированные координаты отличий: x и y от 0 до 1, отсчёт от левого верхнего угла.':'Количество строк и столбцов задаёт сетку. Рекомендуем квадратный исходник; размер сетки — от 2×2 до 8×8.';
 render();
}
function render(){
 const data=current();
 if(type.value==='differences'){
  const list=diffBuilder.querySelector('[data-points-list]'); list.innerHTML='';
  (Array.isArray(data.differences)?data.differences:[]).forEach((point,index)=>{
   const row=document.createElement('div');row.className='game-point-row';
   row.innerHTML=`<label class="field"><span>Отличие ${index+1} · X</span><input type="number" min="0" max="1" step="0.001" data-point="x" value="${Number(point.x)||0}"></label><label class="field"><span>Y</span><input type="number" min="0" max="1" step="0.001" data-point="y" value="${Number(point.y)||0}"></label><label class="field"><span>Размер</span><input type="number" min="0.01" max="0.2" step="0.005" data-point="radius" value="${Number(point.radius)||0.05}"></label><label class="field"><span>Подсказка</span><input maxlength="100" data-point="label" value="${String(point.label||'').replaceAll('&','&amp;').replaceAll('"','&quot;').replaceAll('<','&lt;')}"></label><button type="button" class="button button-danger button-small" data-remove-point="${index}">Удалить</button>`;
   list.append(row);
  });
 }else{
  mosaicBuilder.querySelector('[data-mosaic-rows]').value=data.rows||3;
  mosaicBuilder.querySelector('[data-mosaic-columns]').value=data.columns||3;
 }
}
function savePoints(){const points=[...diffBuilder.querySelectorAll('.game-point-row')].map(row=>{const item={};row.querySelectorAll('[data-point]').forEach(input=>item[input.dataset.point]=input.dataset.point==='label'?input.value:Number(input.value));return item});write({...current(),differences:points})}
type.addEventListener('change',update);area.addEventListener('input',render);update();
examples.addEventListener('click',()=>{write(samples[type.value]);render()});
diffBuilder.addEventListener('click',event=>{if(event.target.closest('[data-add-point]')){const d=current();d.differences=Array.isArray(d.differences)?d.differences:[];if(d.differences.length<20)d.differences.push({x:.5,y:.5,radius:.05,label:''});write(d);render()}const remove=event.target.closest('[data-remove-point]');if(remove){const d=current();d.differences.splice(Number(remove.dataset.removePoint),1);write(d);render()}});
diffBuilder.addEventListener('change',savePoints);
mosaicBuilder.addEventListener('input',()=>write({...current(),rows:Number(mosaicBuilder.querySelector('[data-mosaic-rows]').value),columns:Number(mosaicBuilder.querySelector('[data-mosaic-columns]').value),shuffle:true}));
document.querySelector('[data-copy-prompt]').addEventListener('click',async()=>{
 const prompt=type.value==='differences'?'Сравни две загруженные картинки для детской игры «Найди отличия». Найди 5-8 очевидных, не слишком мелких изменений. Верни только валидный JSON без markdown: {"differences":[{"x":0.25,"y":0.4,"radius":0.045,"label":"короткое описание"}]}. x и y — доли ширины/высоты от 0 до 1, начало координат в левом верхнем углу. Координаты должны указывать на область изменения на обеих картинках одного размера. Не выдумывай изменения, если их нет.':'Подготовь метаданные для детской игры-мозаики по изображению. Верни только JSON без markdown: {"rows":3,"columns":3,"shuffle":true}. Выбери сетку 3x3 для лёгкого изображения, 4x4 для средней сложности или 5x5 для сложного; значения должны быть от 2 до 8.';
 try{await navigator.clipboard.writeText(prompt);document.querySelector('[data-copy-status]').textContent='Промпт скопирован'}catch{document.querySelector('[data-copy-status]').textContent=prompt;}
});
})();
</script>
@endsection
