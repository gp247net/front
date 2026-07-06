{{--
    Shared custom-field renderer for account/auth forms (change_infomation,
    register). Tailwind port of Default's common/render_form_custom_field.blade.php.
    Included directly via `$GP247TemplatePath.'.common.render_form_custom_field'`
    by vendor views (not through the gp247_shop_process_view() fallback), so
    GP247Front must ship its own copy or gp247_check_view() aborts the request.

    Variables (unchanged from vendor):
    - $object: model instance (or []) passed by the including view

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@php
    $type = is_object($object) ? $object->getTable() : '';
    $fields = is_object($object) ? $object->getCustomFields() : [];
    $customFields = gp247_custom_field_list($type);
@endphp

@if (!empty($customFields) && is_countable($customFields) && count($customFields))
    <div class="divider my-4"></div>
    <p class="label mb-3">
        {{ gp247_language_render('admin.custom_field.title') }}
    </p>
    @foreach ($customFields as $keyField => $field)
        <div class="mb-4">
            <label class="label">{{ gp247_language_render($field->name) }}</label>
            @php
                $default  = json_decode($field->default, true);
                $dataForm = [
                    'name' => ($field->option == 'checkbox') ? 'fields['.$field->code.'][]' : 'fields['.$field->code.']',
                    'type' => $field->option,
                    'attribute' => '',
                    'placeholder' => '',
                    'class' => 'input '.($errors->has('fields.'.$field->code) ? 'input-error ' : '').$field->code,
                    'id' => $keyField,
                    'default' => old('fields.'.$field->code, ($fields[$field->code]['text'] ?? '')),
                    'dataFormat' => $default,
                    'css' => 'width: 100%;',
                    'required' => $field->required,
                ];
            @endphp
            {!! gp247_form_render_field($dataForm) !!}

            @if ($errors->has('fields.'.$field->code))
                <p class="text-xs text-red-600 mt-1">{{ $errors->first('fields.'.$field->code) }}</p>
            @endif
        </div>
    @endforeach
@endif
