<div class="bbeditor" data-bbeditor>
    <div class="bbeditor-topbar"><span class="bbeditor-label" id="{{ $id }}Label">{{ $label }}</span><div class="bbeditor-tabs" role="tablist" aria-label="Режим редактора"><button class="bbeditor-tab is-active" type="button" role="tab" aria-selected="true" data-bbe-tab="write">Текст</button><button class="bbeditor-tab" type="button" role="tab" aria-selected="false" data-bbe-tab="preview">Предпросмотр</button></div></div>
    <div class="bbeditor-pane" data-bbe-pane="write">
        <div class="bbeditor-toolbar" role="toolbar" aria-label="Форматирование текста">
            <button type="button" data-bbe-command="b" aria-label="Жирный"><strong>B</strong></button><button type="button" data-bbe-command="i" aria-label="Курсив"><em>I</em></button><button type="button" data-bbe-command="u" aria-label="Подчёркнутый"><u>U</u></button><span aria-hidden="true"></span><button type="button" data-bbe-command="h2">Заголовок</button><button type="button" data-bbe-command="quote">Цитата</button><button type="button" data-bbe-command="list">Список</button>
        </div>
        <textarea id="{{ $id }}" class="story-body-input bbeditor-input" name="{{ $name }}" rows="18" required data-bbe-input aria-labelledby="{{ $id }}Label" aria-describedby="{{ $id }}Help">{{ $value }}</textarea>
        <small class="field-help" id="{{ $id }}Help">Поддерживаются абзацы, жирный и курсивный текст, подчёркивание, заголовки, цитаты и списки.</small>
    </div>
    <div class="bbeditor-preview" data-bbe-pane="preview" hidden aria-live="polite"></div>
</div>
