      <!-- Page Header-->
      <header class="section page-header">
        <!-- RD Navbar-->
        <div class="rd-navbar-wrap">
          <nav class="rd-navbar rd-navbar-classic" data-layout="rd-navbar-fixed" data-sm-layout="rd-navbar-fixed" data-md-layout="rd-navbar-fixed" data-md-device-layout="rd-navbar-fixed" data-lg-layout="rd-navbar-static" data-lg-device-layout="rd-navbar-fixed" data-xl-layout="rd-navbar-static" data-xl-device-layout="rd-navbar-static" data-xxl-layout="rd-navbar-static" data-xxl-device-layout="rd-navbar-static" data-lg-stick-up-offset="100px" data-xl-stick-up-offset="100px" data-xxl-stick-up-offset="100px" data-lg-stick-up="true" data-xl-stick-up="true" data-xxl-stick-up="true">
            <div class="rd-navbar-main-outer">
              <div class="rd-navbar-main">
                <!-- RD Navbar Panel-->
                <div class="rd-navbar-panel">
                  <!-- RD Navbar Toggle-->
                  <button class="rd-navbar-toggle" data-rd-navbar-toggle=".rd-navbar-nav-wrap"><span></span></button>
                  <!-- RD Navbar Brand-->
                  <div class="rd-navbar-brand">
                    <!--Brand--><a class="brand" href="{{ gp247_route_front('front.home') }}"><img class="brand-logo-dark" src="{{ gp247_file(gp247_store_info('logo', ($storeId ?? null))) }}" alt="" width="105" height="44"/>
                      <img class="brand-logo-light" src="{{ gp247_file(gp247_store_info('logo', ($storeId ?? null))) }}" alt="" width="106" height="44"/></a>
                  </div>
                </div>
                <div class="rd-navbar-nav-wrap">
                  <!-- RD Navbar Nav-->
                  <ul class="rd-navbar-nav">
                    @if (!empty(gp247_link_collection()['menu']))
                    @foreach (gp247_link_collection()['menu'] as $url)
                      @if ($url['type'] != 'collection')
                        <li class="rd-nav-item">
                          <a class="rd-nav-link" {{ ($url['data']['target'] =='_blank')?'target=_blank':''  }}
                              href="{{ gp247_url_render($url['data']['url']) }}">{{ gp247_language_render($url['data']['name']) }}</a>
                        </li>
                      @else
                        @if (count($url['childs']))
                        <li class="rd-nav-item"><a class="rd-nav-link" href="#"><i class="fa fa-lock"></i> {{ $url['data']['name'] }}</a>
                          <ul class="rd-menu rd-navbar-dropdown">
                              @foreach ($url['childs'] as $item)
                              <li class="rd-dropdown-item">
                                <a class="rd-nav-link" {{ ($item['data']['target'] =='_blank')?'target=_blank':''  }}
                                  href="{{ gp247_url_render($item['data']['url']) }}">{{ gp247_language_render($item['data']['name']) }}</a>
                              </li>
                              @endforeach
                          </ul>
                      </li>
                        @endif
                      @endif

                    @endforeach
                    @endif

                    @if (gp247_config('link_language', null, 1))
                    @if (count($gp247_languages)>1)
                    <li class="rd-nav-item">
                        <a class="rd-nav-link" href="#">
                            <img src="{{ gp247_file($gp247_languages[app()->getLocale()]['icon']) }}" style="height: 25px;"> <i class="fas fa-caret-down"></i>
                        </a>
                        <ul class="rd-menu rd-navbar-dropdown">
                            @foreach ($gp247_languages as $key => $language)
                            <li class="rd-dropdown-item">
                                <a class="rd-dropdown-link" href="{{ gp247_route_front('front.locale', ['code' => $key]) }}">
                                    <img src="{{ gp247_file($language['icon']) }}" style="height: 25px;"> {{ $language['name'] }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endif
                    @endif

                  </ul>
                </div>

                <div class="rd-navbar-main-element">
                  <!-- RD Navbar Search-->
                  <div class="rd-navbar-search rd-navbar-search-2">
                    <button class="rd-navbar-search-toggle rd-navbar-fixed-element-3" data-rd-navbar-toggle=".rd-navbar-search"><span></span></button>
                    <form class="rd-search" action="{{ gp247_route_front('front.search') }}"  method="GET">
                      <div class="form-wrap">
                        <input class="rd-navbar-search-form-input form-input"  type="text" name="keyword"  placeholder="{{ gp247_language_render('search.placeholder') }}"/>
                        <button class="rd-search-form-submit" type="submit"></button>
                      </div>
                    </form>
                  </div>

                </div>
              </div>
            </div>
          </nav>
        </div>
      </header>
