<div class="field rich-editor-field" data-rich-editor data-initial-html="{{ app(\App\Support\BBEditor::class)->render($value) }}">
    <label class="rich-editor-label" id="{{ $id }}Label">{{ $label }} <span aria-hidden="true">*</span></label>
    <textarea id="{{ $id }}" class="rich-editor-source" name="{{ $name }}" rows="14" @if($required ?? true)required @endif @if(isset($maxlength))maxlength="{{ $maxlength }}" data-rich-max="{{ $maxlength }}" @endif data-rich-source aria-labelledby="{{ $id }}Label" aria-describedby="{{ $id }}Help">{{ $value }}</textarea>
    <div class="rich-editor-shell">
        <div class="rich-editor-tabs" role="tablist" aria-label="Режим редактирования">
            <button class="rich-editor-tab is-active" type="button" role="tab" aria-selected="true" data-rich-tab="edit">Редактор</button>
            <button class="rich-editor-tab" type="button" role="tab" aria-selected="false" data-rich-tab="preview">Предпросмотр</button>
        </div>
        <div class="rich-editor-canvas" data-rich-pane="edit"><div id="{{ $id }}Visual" data-rich-host></div></div>
        <div class="rich-editor-preview" data-rich-pane="preview" hidden aria-live="polite"></div>
    </div>
    <small class="field-help" id="{{ $id }}Help">Форматируйте текст кнопками над полем. Списки, цитаты, заголовки, изображения и базовое выделение сохраняются при публикации. @if(isset($maxlength))<span data-rich-count>0 / {{ $maxlength }}</span>@endif</small>
</div>
