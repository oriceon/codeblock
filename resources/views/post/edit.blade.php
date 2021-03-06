@extends('master')

@section('css')
@stop

@section('content')
	{{ Form::model($post, array('action' => array('PostController@createOrUpdate', $post->id))) }}
		<h2>Edit Codeblock</h2>
		{{ Form::label('blockName', 'Name:') }}
		{{ Form::text('name', Input::old('name'), array('id' => 'blockName', 'placeholder' => 'Name of codeblock', 'data-validator' => 'required|min:3')) }}
		{{ $errors->first('name', '<div class="alert error">:message</div>') }}
		<div class="verticalRule">
			<div class="float-left">
				{{ Form::label('blockCategory', 'Category:') }}
				{{ Form::select('category', $categories, $post->category->id, array('id' => 'blockCategory', 'data-validator' => 'required')) }}
			</div>
			<div class="float-right">
				{{ Form::label('private', 'Private codeblock:') }}<br />
				{{ Form::select('private', array(0 => 'No', 1 => 'Yes'), $post->private) }}
			</div>
		</div>
		{{ Form::label('', 'Tags:') }}
		{{ Form::select('tags[]', $tags, $post->posttags, array('multiple', 'class' => 'chosen-select', 'data-placeholder' => 'Choose some tags')) }}

		{{ Form::label('blockCode', 'Code:') }}
        @if(is_array($post->category->lang))
        {{ Form::textarea('code', Input::old('code'), array('class'=> 'code-editor', 'data-lang' => strtolower($post->category->name) ,'id' => 'blockCode', 'placeholder' => 'Code goes here...', 'data-validator' => 'required|min:3')) }}
        @else
        {{ Form::textarea('code', Input::old('code'), array('class'=> 'code-editor', 'data-lang' => strtolower($post->category->name) ,'id' => 'blockCode', 'placeholder' => 'Code goes here...', 'data-validator' => 'required|min:3')) }}
        @endif
		{{ $errors->first('code', '<div class="alert error">:message</div>') }}

        {{ Form::label('blockDescription', 'Description:', array('class' =>'margin-top-one display-block')) }}
		{{ Form::textarea('description', Input::old('description'), array('id' => 'blockDescription', 'rows' => '2', 'placeholder' => 'Description of codeblock', 'data-validator' => 'required|min:3')) }}
		{{ $errors->first('description', '<div class="alert error">:message</div>') }}

		{{ Form::button('Save', array('type' => 'submit')) }}
	{{ Form::close() }}
@stop

@section('script')
	<script src="{{ asset('js/codemirror/addon/display/placeholder.js') }}"></script>
	@foreach ($categories as $key => $category)
		@if($key != '')
			@if($category == 'Html')
				<script src="{{ asset('js/codemirror/mode/xml/xml.js') }}"></script>
			@elseif(strtolower($category) == 'c#' || strtolower($category) == 'asp.net')
				<script src="{{ asset('js/codemirror/mode/clike/clike.js') }}"></script>
			@else
				<script src="{{ asset('js/codemirror/mode/'.strtolower($category).'/'.strtolower($category).'.js') }}"></script>
			@endif
		@endif
	@endforeach
@stop