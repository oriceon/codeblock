@extends('master')

@section('css')

@stop

@section('content')
	<div class="small-wrapper">
		<h2>
			{{ $post->name }}
			<span class="float-right">
			@if(Auth::check())
					@if(Auth::user()->id != $post->user_id)
						{{HTML::actionlink($url = array('action' => 'PostController@fork', 'params' => array($post->id)), '<i class="fa fa-code-fork"></i>')}}
						{{HTML::actionlink($url = array('action' => 'PostController@forked', 'params' => array($post->id)), $post->forked)}}
					@else
						{{HTML::actionlink($url = array('action' => 'PostController@edit', 'params' => array($post->id)), '<i class="fa fa-pencil"></i>')}}
					@endif
				@endif
				@if(!Auth::check())
					<i class="fa fa-star"></i>
				@elseif(Auth::user()->id == $post->user_id)
					<i class="fa fa-star"></i>
				@elseif ( in_array(Auth::user()->id, $post->stars['userArray']))
					{{HTML::actionlink($url = array('action' => 'PostController@star', 'params' => array($post->id)), '<i class="fa fa-star"></i>')}}
				@else
					{{HTML::actionlink($url = array('action' => 'PostController@star', 'params' => array($post->id)), '<i class="fa fa-star-o"></i>')}}
				@endif
				{{ $post->stars['count'] }}</span>
		</h2>
		<div class="verticalRule">
			<div class="float-left">
				@if(!empty($post->posttags[0]))
					@if(isset($post->category))
						<b>Category:</b> {{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array($post->category->id)), $post->category->name)}}
					@endif
				@else
					<p>
						<b>Posted by:</b> {{HTML::actionlink($url = array('action' => 'UserController@show', 'params' => array($post->user->id)), $post->user->username)}}
						<b>on:</b> {{ date('Y-m-d',strtotime($post->created_at)) }}
					</p>
				@endif
			</div>
			<div class="float-right">
				@if(!empty($post->posttags[0]))
					<b>Tags:</b>
					@foreach ($post->posttags as $tag)
						{{HTML::actionlink($url = array('action' => 'PostController@tag', 'params' => array($tag->id)), $tag->name, array('class' => 'label'))}}
					@endforeach
				@else
					@if(isset($post->category))
						<b>Category:</b> {{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array($post->category->id)), $post->category->name)}}
					@endif
				@endif
			</div>
		</div>
		<hr>
		@if(!is_null($post->org))
			<p class="margin-top-half"><b>Forked from:</b> {{HTML::actionlink($url = array('action' => 'UserController@show', 'params' => array($post->org->user->id)), $post->org->user->username)}}/{{HTML::actionlink($url = array('action' => 'PostController@show', 'params' => array($post->org->id)), $post->org->name)}}</p>
			<hr>
		@endif
		<p id="description"><b>Description:</b> {{ $post->description }}</p>
		<p><b>Code:</b></p>
		@if(isset($post->category->name))
			<textarea class="code-editor readonly" data-lang="{{ strtolower($post->category->name) }}" id="blockCode">{{ $post->code }}</textarea>
		@else
			<textarea class="code-editor readonly" data-lang="xml" id="blockCode">{{ $post->code }}</textarea>
		@endif
		@if ($post->private != 1)
			@if(count($post->comments) > 0)
				<h2 class="margin-top-half">Comments</h2>
				@foreach ($post->comments as $comment)
					@if($comment->status == 0)
						@if($comment->parent == 0)
							<div class="comment" id="comment-{{ $comment->id}}">
								<div>
									@if(Auth::check() && Auth::user()->id != $comment->user_id)
										@if ($rate->check($comment->id) == '+')
											{{ $rate->calc($comment->id) }}
											{{HTML::actionlink($url = array('action' => 'RateController@minus', 'params' => array($comment->id)), '<i class="fa fa-caret-down"></i>')}}
										@elseif($rate->check($comment->id) == '-')
											{{HTML::actionlink($url = array('action' => 'RateController@plus', 'params' => array($comment->id)), '<i class="fa fa-caret-up"></i>')}}
											{{ $rate->calc($comment->id) }}
										@else
											{{HTML::actionlink($url = array('action' => 'RateController@plus', 'params' => array($comment->id)), '<i class="fa fa-caret-up"></i>')}}
											{{ $rate->calc($comment->id) }}
											{{HTML::actionlink($url = array('action' => 'RateController@minus', 'params' => array($comment->id)), '<i class="fa fa-caret-down"></i>')}}
										@endif
									@else
										{{ $rate->calc($comment->id) }}
									@endif
								</div>
								<div>
									<b>{{ date('Y-m-d', strtotime($comment->created_at)) }}</b> - {{HTML::actionlink($url = array('action' => 'UserController@show', 'params' => array($comment->user_id)), $comment->user['username'])}}
									<p>{{ HTML::mention(HTML::markdown($comment->comment)) }}</p>
									<a class="reply" href="#comment-{{$comment->id}}">Reply</a>
									@include('comment.child')
								</div>
							</div>
						@endif
					@endif
				@endforeach
			@endif
			@if(Auth::check())
				<div id="comment">
					{{ Form::model(null, array('action' => array('CommentController@createOrUpdate'))) }}
					<h3><a class="float-left close-reply" href="#comment">Cancel</a> Make a comment</h3>
					{{ Form::hidden('post_id', $post->id); }}
					{{ Form::hidden('parent'); }}
					{{ Form::textarea('comment', Input::old('comment'), array('id' => 'comment', 'class' => 'mentionarea', 'rows' => '2', 'placeholder' => 'Your comment', 'data-validator' => 'required|min:3')) }}
					{{ $errors->first('comment', '<div class="alert error">:message</div>') }}
					{{ Form::button('Comment', array('type' => 'submit')) }}
					{{ Form::close() }}
				</div>
			@endif
		@endif
	</div>
@stop

@section('script')
	@if(count($lang) > 1)
		@foreach($lang as $la)
			<script src="{{ asset('js/codemirror/mode/'.$la.'/'.$la.'.js') }}"></script>
		@endforeach
	@else
		<script src="{{ asset('js/codemirror/mode/'.$lang.'/'.$lang.'.js') }}"></script>
	@endif
@stop                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       