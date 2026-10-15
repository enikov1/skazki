<div class="field genre-picker" data-custom-select>
    <span id="{{ $id }}Label">Жанр</span>
    <select class="genre-picker-native" name="genre" required tabindex="-1" aria-hidden="true" data-custom-select-native aria-labelledby="{{ $id }}Label">
        <option value="">Выберите жанр</option>
        @foreach($genres as $genre)
            <option value="{{ $genre->name }}" @selected($selected === $genre->name)>{{ $genre->name }}</option>
        @endforeach
    </select>
    <button class="genre-picker-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="{{ $id }}Label" data-custom-select-trigger>
        <span data-custom-select-value>{{ $selected ?: 'Выберите жанр' }}</span><span class="genre-picker-chevron" aria-hidden="true"></span>
    </button>
    <div class="genre-picker-menu" hidden data-custom-select-menu>
        <label class="genre-picker-search"><span class="sr-only">Найти жанр</span><input type="search" placeholder="Поиск жанра…" autocomplete="off" data-custom-select-search></label>
        <div class="genre-picker-options" role="listbox" aria-labelledby="{{ $id }}Label">
            @foreach($genres as $genre)
                <button type="button" role="option" aria-selected="{{ $selected === $genre->name ? 'true' : 'false' }}" data-custom-select-option data-value="{{ $genre->name }}">{{ $genre->name }}</button>
            @endforeach
        </div>
        @if(auth()->user()->hasRole('admin'))<a class="genre-picker-manage" href="{{ route('admin.genres.index') }}">Управлять жанрами</a>@endif
    </div>
</div>
