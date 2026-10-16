@extends('layouts.app')
@section('title', $game->title.' — '.$game->type_label.' | СказкоМир')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('games.index') }}">Игры</a><span>/</span><a href="{{ $type === 'differences' ? route('games.differences.index') : route('games.mosaics.index') }}">{{ $game->type_label }}</a><span>/</span><span aria-current="page">{{ $game->title }}</span></nav>
<header class="page-panel games-heading"><p class="eyebrow">{{ $game->type_label }}</p><h1 class="page-title">{{ $game->title }}</h1>@if($game->description)<p class="lead">{{ $game->description }}</p>@endif</header>
<section class="interactive-game" data-interactive-game data-type="{{ $type }}" data-payload="{{ json_encode($game->data, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP) }}">
@if($type === 'differences')
<p class="game-instruction">Найдите все отличия на картинках. Нажимайте на место отличия на любой картинке. Найдено: <strong data-found>0</strong> из {{ count($game->data['differences'] ?? []) }}</p>
<div class="difference-images">@foreach([$game->image_a, $game->image_b] as $image)<div class="difference-image" data-difference-image><img src="{{ asset($image) }}" alt="Картинка для игры {{ $game->title }}"><div class="difference-markers" aria-hidden="true"></div></div>@endforeach</div>
@else
<p class="game-instruction">Нажимайте на две части, чтобы поменять их местами. Собрано: <strong data-mosaic-progress>0%</strong></p>
<div class="mosaic-board" data-mosaic-board data-image-src="{{ asset($game->image_a) }}" role="group" aria-label="Мозаика {{ $game->title }}"></div>
@endif
<div class="game-finish" data-game-finish hidden><strong>Отлично! Задание выполнено.</strong><button type="button" class="button button-soft" data-game-restart>Играть ещё раз</button></div>
</section>
<script>
(() => {
 const root=document.querySelector('[data-interactive-game]'); if(!root)return;
 const type=root.dataset.type, payload=JSON.parse(root.dataset.payload);
 if(type==='differences'){
  const images=[...root.querySelectorAll('[data-difference-image]')], found=new Set(), output=root.querySelector('[data-found]');
  images.forEach((wrap,index)=>wrap.addEventListener('click',event=>{
   const rect=wrap.getBoundingClientRect(), x=(event.clientX-rect.left)/rect.width, y=(event.clientY-rect.top)/rect.height;
   const match=(payload.differences||[]).findIndex((point,i)=>!found.has(i)&&Math.hypot((x-point.x)*rect.width,(y-point.y)*rect.height)<=Math.max(point.radius||.05,.025)*rect.width);
   if(match<0)return;
   found.add(match);output.textContent=found.size;
   images.forEach(image=>{const marker=document.createElement('span');marker.className='difference-hit';marker.style.left=`${payload.differences[match].x*100}%`;marker.style.top=`${payload.differences[match].y*100}%`;image.querySelector('.difference-markers').append(marker)});
   if(found.size===(payload.differences||[]).length)root.querySelector('[data-game-finish]').hidden=false;
  }));
 } else {
  const board=root.querySelector('[data-mosaic-board]'), rows=payload.rows||3, cols=payload.columns||3, count=rows*cols;
  const sourceImage=new Image();sourceImage.onload=()=>{board.style.aspectRatio=`${sourceImage.naturalWidth}/${sourceImage.naturalHeight}`};sourceImage.src=board.dataset.imageSrc;board.style.setProperty('--mosaic-image',`url("${board.dataset.imageSrc}")`);
  let order=Array.from({length:count},(_,i)=>i), selected=-1;
  function draw(){board.style.setProperty('--rows',rows);board.style.setProperty('--columns',cols);board.innerHTML='';order.forEach((piece,pos)=>{const tile=document.createElement('button');tile.type='button';tile.className='mosaic-tile';tile.setAttribute('aria-label',`Фрагмент ${piece+1}`);tile.style.backgroundPosition=`${cols===1?0:(piece%cols)*100/(cols-1)}% ${rows===1?0:Math.floor(piece/cols)*100/(rows-1)}%`;tile.style.backgroundSize=`${cols*100}% ${rows*100}%`;tile.addEventListener('click',()=>{if(selected<0){selected=pos;tile.classList.add('selected');return} [order[selected],order[pos]]=[order[pos],order[selected]];selected=-1;draw()});board.append(tile)});const correct=order.filter((piece,pos)=>piece===pos).length;root.querySelector('[data-mosaic-progress]').textContent=Math.round(correct/count*100)+'%';if(correct===count)root.querySelector('[data-game-finish]').hidden=false}
  function shuffle(){for(let i=order.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[order[i],order[j]]=[order[j],order[i]]}if(order.every((n,i)=>n===i))[order[0],order[1]]=[order[1],order[0]];selected=-1;root.querySelector('[data-game-finish]').hidden=true;draw()}
  root.querySelector('[data-game-restart]').addEventListener('click',shuffle);shuffle();return;
 }
 root.querySelector('[data-game-restart]')?.addEventListener('click',()=>location.reload());
})();
</script>
@endsection
