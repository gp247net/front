@extends('gp247-core::layout')

@section('main')
   <div class="row">
      <div class="col-md-12">
         <div class="card">
                <div class="card-header with-border">
                    <h2 class="card-title">{{ $title_description??'' }}</h2>

                    <div class="card-tools">
                        <div class="btn-group float-right mr-5">
                            <a href="{{ gp247_route_admin('admin_banner.index') }}" class="btn  btn-flat btn-default" title="List"><i class="fa fa-list"></i><span class="hidden-xs"> {{ gp247_language_render('admin.back_list') }}</span></a>
                        </div>
                    </div>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form action="{{ $url_action }}" method="post" accept-charset="UTF-8" class="form-horizontal" id="form-main"  enctype="multipart/form-data">


                    <div class="card-body">
                        <div class="fields-group">

                            <div class="form-group  row {{ $errors->has('image') ? ' text-red' : '' }}">
                                <label for="image" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.image') }}</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" id="image" name="image" value="{{ old('image',$banner['image']??'') }}" class="form-control image" placeholder=""  />
                                        <div class="input-group-append">
                                         <a data-input="image" data-preview="preview_image" data-type="banner" class="btn btn-primary lfm">
                                           <i class="fa fa-image"></i> {{gp247_language_render('action.choose_image')}}
                                         </a>
                                        </div>
                                    </div>
                                        @if ($errors->has('image'))
                                            <span class="form-text">
                                                <i class="fa fa-info-circle"></i> {{ $errors->first('image') }}
                                            </span>
                                        @endif
                                    <div id="preview_image" class="img_holder">
                                        @if (old('image',$banner['image']??''))
                                        <img src="{{ gp247_file(old('image',$banner['image']??'')) }}">
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group  row {{ $errors->has('url') ? ' text-red' : '' }}">
                                <label for="url" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.url') }}</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                            </div>
                                        </div>
                                        <input type="text" id="url" name="url" value="{{ old()?old('url'):$banner['url']??'' }}" class="form-control" placeholder="" />
                                    </div>
                                        @if ($errors->has('url'))
                                            <span class="form-text">
                                                <i class="fa fa-info-circle"></i> {{ $errors->first('url') }}
                                            </span>
                                        @endif
                                </div>
                            </div>


                            <div class="form-group  row {{ $errors->has('title') ? ' text-red' : '' }}">
                                <label for="title" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.title') }}</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                            </div>
                                        </div>
                                        <input type="text" id="title" name="title" value="{{ old()?old('title'):$banner['title']??'' }}" class="form-control" placeholder="" />
                                    </div>
                                        @if ($errors->has('title'))
                                            <span class="form-text">
                                                <i class="fa fa-info-circle"></i> {{ $errors->first('title') }}
                                            </span>
                                        @endif
                                </div>
                            </div>


                            <div class="form-group row {{ $errors->has('target') ? ' text-red' : '' }}">
                                    <label for="target" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.select_target') }}</label>
                                    <div class="col-sm-8">
                                        <select class="form-control target select2" style="width: 100%;" name="target" >
                                            <option value=""></option>
                                            @foreach ($arrTarget as $k => $v)
                                                <option value="{{ $k }}" {{ (old('target',$banner['target']??'') ==$k) ? 'selected':'' }}>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                            @if ($errors->has('target'))
                                                <span class="form-text">
                                                    <i class="fa fa-info-circle"></i> {{ $errors->first('target') }}
                                                </span>
                                            @endif
                                    </div>
                                </div>

                            <div class="form-group row {{ $errors->has('html') ? ' text-red' : '' }}">
                                <label for="html" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.email_template.html') }}</label>
                                <div class="col-sm-8">
                                        <textarea class="form-control" rows="10" id="html" name="html">{{ old('html',$banner['html']??'') }}</textarea>
                                        @if ($errors->has('html'))
                                            <span class="form-text">
                                                <i class="fa fa-info-circle"></i> {{ $errors->first('html') }}
                                            </span>
                                        @endif
                                </div>
                            </div>
                            
                            <div class="form-group row {{ $errors->has('type') ? ' text-red' : '' }}">
                                <label class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.type') }}</label>
                                <div class="col-sm-8">
                                <div class="input-group">
                                <select class="form-control select2" name="type">
                                    @foreach ($dataType as $key => $name)
                                    <option {{ (old('type', $banner['type']??'') ==  $key)?'selected':'' }} value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <a href="{{ gp247_route_admin('admin_banner_type.index') }}" class="btn  btn-flat" title="New">
                                        <i class="fa fa-plus" title="{{ gp247_language_render('action.add') }}"></i>
                                     </a>
                                </div>
                                </div>
                                @if ($errors->has('type'))
                                <span class="form-text">
                                    {{ $errors->first('type') }}
                                </span>
                                @endif
                                </div>
                              </div>
                              
                            <div class="form-group  row {{ $errors->has('sort') ? ' text-red' : '' }}">
                                <label for="sort" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.sort') }}</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                        </div>
                                        <input type="number" style="width: 100px;" min = 0 id="sort" name="sort" value="{{ old('sort',$banner['sort']??0) }}" class="form-control sort" placeholder="" />
                                    </div>
                                        @if ($errors->has('sort'))
                                            <span class="form-text">
                                                <i class="fa fa-info-circle"></i> {{ $errors->first('sort') }}
                                            </span>
                                        @endif
                                </div>
                            </div>

@if (gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed())
                            {{-- select shop_store --}}
                            @php
                            $listStore = [];
                            if (function_exists('gp247_get_list_store_of_banner_detail')) {
                                $oldData = gp247_get_list_store_of_banner_detail($banner['id'] ?? '');
                            } else {
                                $oldData = null;
                            }
                            $shop_store = old('shop_store', $oldData);

                            if(is_array($shop_store)){
                                foreach($shop_store as $value){
                                    $listStore[] = $value;
                                }
                            }
                            @endphp
    
                            <div class="form-group row {{ $errors->has('shop_store') ? ' text-red' : '' }}">
                                <label for="shop_store"
                                    class="col-sm-2 col-form-label">{{ gp247_language_render('admin.select_store') }}</label>
                                <div class="col-sm-8">
                                    <select class="form-control shop_store select2" 
                                    @if (gp247_store_check_multi_store_installed())
                                        multiple="multiple"
                                    @endif
                                    data-placeholder="{{ gp247_language_render('admin.select_store') }}" style="width: 100%;"
                                    name="shop_store[]">
                                        <option value=""></option>
                                        @foreach (gp247_store_get_list_code() as $k => $v)
                                        <option value="{{ $k }}"
                                            {{ (count($listStore) && in_array($k, $listStore))?'selected':'' }}>{{ $v }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('shop_store'))
                                    <span class="form-text">
                                        <i class="fa fa-info-circle"></i> {{ $errors->first('shop_store') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            {{-- //select shop_store --}}
    @endif
                            <div class="form-group row ">
                                <label for="status" class="col-sm-2 col-form-label">{{ gp247_language_render('admin.banner.status') }}</label>
                                <div class="col-sm-8">
                                    <input class="checkbox" type="checkbox" name="status"  {{ old('status',(empty($banner['status'])?0:1))?'checked':''}}>
                                </div>
                            </div>


                        {{-- Custom fields --}}
                        @includeIf('gp247-core::component.render_form_custom_field', ['object' => $banner])
                        {{-- //Custom fields --}}


                    <!-- /.card-body -->

                    <div class="card-footer row">
                            @csrf
                        <div class="col-md-2">
                        </div>

                        <div class="col-md-8">
                            <div class="btn-group float-right">
                                <button type="submit" class="btn btn-primary">{{ gp247_language_render('action.submit') }}</button>
                            </div>
    
                            <div class="btn-group float-left">
                                <button type="reset" class="btn btn-warning">{{ gp247_language_render('action.reset') }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- /.card-footer -->
                </form>

        </div>
    </div>
</div>
@endsection

@push('styles')

@endpush

@push('scripts')
@include('gp247-core::component.ckeditor_js')


<script type="text/javascript">
    $(document).ready(function() {
    $('.select2').select2()
});

</script>

<script type="text/javascript">
    $('textarea.editor').ckeditor(
    {
        filebrowserImageBrowseUrl: '{{ gp247_route_admin('admin.home').'/'.config('lfm.url_prefix') }}?type=banner',
        filebrowserImageUploadUrl: '{{ gp247_route_admin('admin.home').'/'.config('lfm.url_prefix') }}/upload?type=banner&_token={{csrf_token()}}',
        filebrowserBrowseUrl: '{{ gp247_route_admin('admin.home').'/'.config('lfm.url_prefix') }}?type=files',
        filebrowserUploadUrl: '{{ gp247_route_admin('admin.home').'/'.config('lfm.url_prefix') }}/upload?type=file&_token={{csrf_token()}}',
        filebrowserWindowWidth: '900',
        filebrowserWindowHeight: '500'
    }
);
</script>

@endpush