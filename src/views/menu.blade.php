<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="{{URL::route('admin.home')}}">Admin</a>
            <button aria-controls="navbar" aria-expanded="false" class="navbar-toggle collapsed" data-target="#navbar" data-toggle="collapse" type="button">
                <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
        </div>
        @if(Auth::check())

            <div id="navbar" class="navbar-collapse collapse">
                @if(Auth::user()->role == 'admin')
                    {{--//CHECK ROLE ADMIN--}}
                    @foreach(BaseModelFields::getRoutesName() as $route)
                        <ul class="nav navbar-nav">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                                   aria-haspopup="true"
                                   aria-expanded="false">{{BaseModelFields::labelFromRouteName($route)}}<span
                                            class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    @if($route != App::make('config')->get('basemodelfields.route_prefix').'.audit')
                                        <li><a href="{{URL::route(BasemodelFields::form_route($route))}}"><i
                                                        class="icon-pencil"></i> Add</a></li>
                                    @endif
                                    <li><a href="{{URL::route(BasemodelFields::get_route($route))}}"><i
                                                    class="icon-list"></i> List</a></li>
                                </ul>
                            </li>
                        </ul>
                    @endforeach

                @endif


                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            {{ Auth::user()->name }} <span class="caret"></span>
                        </a>

                        <ul class="dropdown-menu" role="menu">
                            <li><a href="{{ route('admin.logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    Logout
                                </a>

                                <form id="logout-form" action="{{ route('admin.logout') }}" method="POST"
                                      style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>

            </div>

        @endif
    </div>
</nav>
