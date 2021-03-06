@extends('master')

@section('css')

@stop

@section('content')
	<h2>Browse</h2>
	<div id="accordion" class="accordion">
		<ul>
			<li class="open">
				<a href="#">Categories</a>
				<div class="content">
					{{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array(0)), 'What is new?', array('class' => 'block'))}}
					@foreach ($categories as $category)
						{{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array($category->id)), $category->name, array('class' => 'block'))}}
					@endforeach
				</div>
			</li>
			<li>
				<a href="">Tags</a>
				<div class="content" id="tagList">
					@foreach ($tags as $tag)
						{{HTML::actionlink($url = array('action' => 'PostController@tag', 'params' => array($tag->id)), $tag->name, array('class' => 'block'))}}
					@endforeach
				</div>
			</li>
		</ul>
	</div>
	<div id="browseTabs" class="tabs">
		<ul class="clearfix">
			<li class="open"><a href="">Categories</a></li>
			<li><a href="">Tags</a></li>
		</ul>
		<ul>
			<li class="open">
				{{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array(0)), 'What is new?', array('class' => 'block'))}}
				@foreach ($categories as $category)
					{{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array($category->id)), $category->name, array('class' => 'block'))}}
				@endforeach
				<div class="clear"></div>
			</li>
			<li>
				<div id="tagList">
					@foreach ($tags as $tag)
						{{HTML::actionlink($url = array('action' => 'PostController@tag', 'params' => array($tag->id)), $tag->name, array('class' => 'block'))}}
					@endforeach
				</div>
			</li>
		</ul>
	</div>
@stop

@section('script')

@stop