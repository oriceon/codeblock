<?php namespace App\Http\Controllers;

use App\NotificationType;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Post\PostRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Tag\TagRepository;
use App\Repositories\Rate\RateRepository;
use App\Services\Github;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'PostController@index');
	|
	*/

	public function __construct(PostRepository $post, CategoryRepository $category, TagRepository $tag, RateRepository $rate)
	{
		parent::__construct();
		$this->post = $post;
		$this->category = $category;
		$this->tag = $tag;
		$this->tags = $this->tag->get();
		$this->categories = $this->category->get();
		$this->rate = $rate;
	}
	/**
	 * vissar index vyn.
	 * @permission view_posts
	 * @return objekt objekt med allt som skall skickas till index vyn
	 */
	public function index()
	{
		return View::make('post.index')->with('title', 'Posts')->with('posts', $this->post->get());
	}
	/**
	 * Visar enstaka blocks vy
	 * @param  int $id id på blocket som skall vissas
	 * @permission view_private_post:optional
	 * @return array     Typ av medelande och meddelande
	 */
	public function show($id){
		$post = $this->post->get($id);
		$lang = $this->post->jsSwitch($post->category->name);
		if($post->private != 1){
			return View::make('post.show')->with('title', 'show')->with('post', $post)->with('rate', $this->rate)->with('lang', $lang);
		}else{
			if(Auth::check()){
				if(!empty($post->comments[0])){
					$post->comments = usort($post->comments->toArray(), function($a, $b) { return strcmp($this->rate->calc($a->id),$this->rate->calc($b->id)); });
				}
				if(Auth::user()->id == $post->user_id || Auth::user()->hasPermission($this->getPermission(), false)){
					return View::make('post.show')->with('title', 'show')->with('post', $post)->with('lang', $lang);
				}else{
					return Redirect::back()->with('error', 'You have no access to that codeblock.');
				}
			}else{
				return Redirect::back()->with('error', 'You have no access to that codeblock.');
			}
		}
	}
	/**
	 * Visar vyn för att skapa block.
	 * @return objekt objekt med allt som skall skickas till create vyn
	 */
	public function create(Github $github)
	{
		return View::make('post.create')->with('title', 'create')->with('post', null)->with('tags', $this->selectTags())->with('categories', $this->selectCategories())->with('hasRequest', $github->hasRequestLeft());
	}
	/**
	 * Är vyn skapa och redigera anropa för att lägga till/ uppdatera blocket i databasen.
	 * @param  int $id Id på det blocket som skall redigeras
	 * @return array     Typ av medelande och meddelande
	 */
	public function createOrUpdate($id = null)
	{
		if($this->post->createOrUpdate(Input::all(), $id)){
			return Redirect::back()->with('success', 'Your block has been saved, You can see your new codeblock <a href="/posts/'.$this->post->getId().'">here</a>.');
		}

		return Redirect::back()->withErrors($this->post->getErrors())->withInput();
	}

	/**
	 * Visar vyn för det blocket som skall redigeras
	 * @permission admin_edit_post:optional
	 * @param  int $id Id på det blocket som skall redigeras
	 * @return objekt objekt med allt som skall skickas till edit vyn
	 */
	public function edit($id)
	{
		$post = $this->post->get($id);
		if(Auth::user()->id != $post->user_id && !Auth::user()->hasPermission($this->getPermission(), false)){
			return Redirect::back()->with('error', 'That codeblock is not yours.');
		}
		$tagsarray = array();
		foreach ($post->posttags as $tag) {
			$tagsarray[] = $tag->id;
		}
		$post->posttags = $tagsarray;

		return View::make('post.edit')->with('title', 'Edit')->with('post', $post)->with('tags', $this->selectTags())->with('categories', $this->selectCategories());
	}

	/**
	 * Skapa en array med kategorier för select elementen i vyerna
	 * @return array  en associstiv array. (id => name)
	 */
	private function selectCategories(){
		$selectArray = array();
		$selectArray[''] = 'Codeblock category';
		foreach ($this->categories as $category) {
			$selectArray[$category->id] = $category->name;
		}
		return $selectArray;
	}

	/**
	 * Skapa en array med ettiketer för select elementen i vyerna
	 * @return array  en associstiv array. (id => name)
	 */
	private function selectTags(){
		$selectArray = array();
		foreach ($this->tags as $tag) {
			$selectArray[$tag->id] = $tag->name;
		}
		return $selectArray;
	}

	/**
	 * Ta bort ett block.
	 * @permission delete_post:optional
	 * @param  int $id Id för blocket som skall tas bort.
	 * @return array     Typ av medelande och meddelande
	 */
	public function delete($id)
	{
		$post = $this->post->get($id);
		if(!is_null($post)) {
			if(Auth::check() && Auth::user()->id == $post->user_id || Auth::user()->hasPermission($this->getPermission(), false)) {
				if($this->post->delete($id)) {
					return Redirect::back()->with('success', 'Your codeblock has been deleted.');
				}
			}else{
				return Redirect::back()->with('error', 'You do not have permission to delete that codeblock.');
			}
		}
		return Redirect::back()->with('error', 'We could not delete that codeblock.');
	}

	/**
	 * Visar en vy med alla block.
	 * @return objekt objekt med alla block som skall visas.
	 */
	public function listPosts(){
		return View::make('post.list')->with('title', 'All Codeblocks')->with('posts', $this->post->get());
	}

	/**
	 * Visar vyn för sökresultatet
	 * @return objekt med vyn som skall vissas med alla variabler som behövs.
	 */
	public function search(){
		return View::make('post.list')->with('title', 'Search on: '.trim(strip_tags(Input::get('term'))))->with('posts', $this->post->search(Input::get('term')));
	}

	/**
	 * Vissar alla block i en kategori.
	 * @param  int $id id på kategorin som blocken skall tillhöra.
	 * @return objekt med vyn som skall vissas med alla variabler som behövs.
	 */
	public function category($id){
		if($id != 0){
			$category = $this->category->get($id);
		}else{
			$this->category->name = "What's new";
			$category = $this->category;
		}
		return View::make('post.list')->with('title', 'Posts in category: '.$category->name )->with('posts', $this->post->getByCategory($id))->with('category', $category);
	}

	/**
	 * Vissar alla block som tillhör en ettiket.
	 * @param  int $id id på ettiketen som blocken skall tillhöra.
	 * @return objekt med vyn som skall vissas med alla variabler som behövs.
	 */
	public function tag($id){
		$tag = $this->tag->get($id);
		return View::make('post.list')->with('title', 'Posts with tag: '.$tag->name)->with('posts', $this->post->getByTag($id))->with('tag', $tag);
	}

	/**
	 * stjärnmärker ett block
	 * @param  int $id id för blocket som skall stjärnmärka.
	 * @return array     Typ av medelande och meddelande
	 */
	public function star(NotificationRepository $notification, $id){
		$star = $this->post->createOrDeleteStar($id);
		if($star[0]){
			if($star[1] == 'create'){
				$post = $this->post->get($id);
				$notification->send($post->user_id, NotificationType::STAR, $post);
				return Redirect::back()->with('success', 'You have now add a star to this codblock.');
			}
			return Redirect::back()->with('success', 'You have now removed a star from this codblock.');
		}
		return Redirect::back()->with('error', 'Something went wrong, please try again.');
	}

	/**
	 * Duplicerar ett kodblock
	 * @param  int $id id för blocket som skall dupliceras.
	 * @return array     Typ av medelande och meddelande
	 */
	public function fork($id){
		if($this->post->duplicate($id)){
			return Redirect::to('/posts/edit/'.$this->post->getId())->with('success', 'Your have forked a block and can now edit.');
		}
		return Redirect::back()->with('error', 'We could not fork this codeblock right now, please try again.');
	}

	/**
	 * Visar en lista med kodblock som har fokats.
	 * @param  int $id id för det blocket som har forkats
	 * @return objekt med vyn som skall vissas med alla variabler som behövs.
	 */
	public function forked($id){
		$post = $this->post->get($id);
		return View::make('post.list')->with('title', 'Forked codeblock from: '.$post->name)->with('posts', $this->post->getForked($id));
	}

	public function forkGist(Github $github){
		if($github->hasRequestLeft()) {
			$id = Input::get('id');
			if(is_numeric($id)) {
				$data = $github->getGist($id);
				if($data) {
					$category = strtolower($data['language']);
					$category_Id = 1;

					foreach($this->selectCategories() as $key => $value) {
						if($category == strtolower($value)) {
							$category_Id = $key;
							break;
						}
					}

					$data = array('name' => $data['filename'], 'description' => 'A forked <a href="https://api.github.com/gists/' . $id . '" target="_blank">gist</a>', 'category' => $category_Id, 'code' => $data['content']);

					if($this->post->createOrUpdate($data)) {
						return Redirect::to('/posts/' . $this->post->getId())->with('success', 'The requested <a href="https://gist.github.com/' . $id . '" target="_blank">gist</a> have been forked.');
					}
				}
			}
			return Redirect::back()->with('error', 'The requested <a href="https://gist.github.com/' . $id . '" target="_blank">gist</a> could not be forked.');
		}
		return Redirect::back()->with('error', 'Sorry right now we not have any api request left please try agian later.');
	}
}