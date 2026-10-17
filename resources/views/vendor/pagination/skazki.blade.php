@if ($paginator->hasPages())
  <nav class="pagination" aria-label="Пагинация">
    @if ($paginator->onFirstPage())
      <span aria-disabled="true" aria-label="Предыдущая страница">‹</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая страница">‹</a>
    @endif
    <span aria-current="page">Страница {{ $paginator->currentPage() }}</span>
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая страница">›</a>
    @else
      <span aria-disabled="true" aria-label="Следующая страница">›</span>
    @endif
  </nav>
@endif
