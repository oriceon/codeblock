<?php namespace App\Services;

use App\Services\Annotation\Permission;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;

/**
 * Class HtmlBuilder
 * @package App\Services
 */
class HtmlBuilder extends \Illuminate\Html\HtmlBuilder{

	/**
	 * @param $value
	 * @param int $size
	 * @return string
	 */
	public function avatar($value, $size = 48){
		$identicon = new \Identicon\Identicon();
		return $identicon->getImageDataUri($value, $size, '272822');
		//<img alt="Avatar for {{username}}" src="{{HTML::avatar(id)}}">
	}

	/**
	 * @param $text
	 * @return mixed|string
	 */
	public function markdown($text){
		$text = htmlentities($text);
		// Inspirerad och hämtat delar från: https://gist.github.com/jbroadway/2836900
		$rules = array (
			'/\[([^\[]+)\]\(([^\)]+)\)/' => '<a href=\'\2\'>\1</a>',
			'/(\*\*|__)(.*?)\1/' => '<strong>\2</strong>',
			'/(\*|_)(.*?)\1/' => '<i>\2</i>',
			'/\:\"(.*?)\"\:/' => '<q>\1</q>',
			//'/```(.*?)```/' => '<pre>\1</pre>',
			'/`(.*?)`/' => '<code>\1</code>'
		);
		foreach ($rules as $regex => $replacement) {
			$text = preg_replace($regex, $replacement, $text);
		}
		return $text;
	}

	/**
	 * @param $path
	 * @return string
	 */
	public function version($path){
		return asset($path).'?v='.filemtime(public_path().'/'.$path);
	}

	/**
	 * @param $text
	 * @return mixed
	 */
	public function mention($text){
		// Found at: http://granades.com/2009/04/06/using-regular-expressions-to-match-twitter-users-and-hashtags/
		return preg_replace('/(^|\s)@(\w+)/', ' <a class="mention" target="_blank" href="'.action('MenuController@index').'/user/\2">@\2</a>', $text);
	}

	/**
	 * @return string
	 */
	public function flash(){
		$flash = array('success','error', 'warning', 'info');
		foreach ($flash as $value) {
			if(Session::has($value)) {
				return '<div class="text-center alert '.$value.'">' . Session::get($value) . ' <a href="#" class="close-alert">X</a></div>';
			}
		}
	}

	public function toast(){
		$flash = array('success','error', 'warning', 'info');
		foreach ($flash as $value) {
			if(Session::has($value)) {
				return '<div class="toast animated lightSpeedIn '.$value.'"><a href="#" class="close-toast">X</a> ' . Session::get($value) . '</div>';
			}
		}
	}

	/**
	 * @param $content
	 * @param $items
	 * @return string
	 */
	public function submenu($content, $items){
		$list = '';
		foreach($items as $item){
			$list .= $this->menulink($item[0], $item[1], array(), false);
		}
		if($list == ''){
			return $list;
		}
		return '<li class="dropdown"><a class="hideUl" href="">'.$content.'</a><ul>'.$list.'</ul></li>';
	}

	/**
	 * @param $action
	 * @param bool $optional
	 * @return bool
	 */
	public function hasPermission($action, $optional = false){
		$action = explode('@', $action);
		$permissionAnnotation = New Permission('App\\Http\\Controllers\\'.$action[0]);

		if (Auth::check() && !Auth::user()->hasPermission($permissionAnnotation->getPermission($action[1], $optional))) {
			return false;
		}
		return true;
	}

	/**
	 * @param $url
	 * @param $content
	 * @param array $attributes
	 * @param bool $optional
	 * @return string
	 */
	public function menulink($url, $content, $attributes = array(), $optional = true){
		$link = $this->actionlink($url, $content, $attributes, $optional);
		if($link !== ''){
			$link = '<li>'.$link.'</li>';
		}
		return $link;
	}

	/**
	 * @param $url
	 * @param $content
	 * @param array $attributes
	 * @param bool $optional
	 * @return string
	 */
	public function actionlink($url, $content, $attributes = array(), $optional = true){
		if (!$this->hasPermission($url['action'], $optional)){
			return '';
		}

		$url = array_merge(array('action' => '', 'params' => array()), $url);

		$attributes['href'] = URL::action($url['action'], $url['params']);
		if(Str::contains($attributes['href'],Request::path()) ) {
			if(isset($attributes['class'])){
				$attributes['class'] .= ' active';
			}else {
				$attributes['class'] = 'active';
			}
		}

		return '<a'.$this->attributes($attributes).'>'.$content.'</a>';
	}

	/**
	 * @param array $fields
	 * @param array $data
	 * @param array $show
	 * @param $info
	 * @return string
	 */
	public function table($fields = array(), $data = array(), $show = array(), $info){

		if(count($data) > 0){
			$show = array_merge(array('Edit' => false, 'Delete' => false, 'View' => false, 'Pagination' => 0), $show);

			if(!is_array($data)){
				$data = $data->toArray();
			}

			if(!isset($_GET['page']) || !is_numeric($_GET['page'])){
				$_GET['page'] = 1;
			}

			$paginator = new LengthAwarePaginator($data, count($data), $show['Pagination'], $_GET['page'], array('path' => Request::path()));
			if($show['Pagination'] > 0){
				$data = array_slice($data, ($_GET['page'] * $show['Pagination']) - $show['Pagination'], $show['Pagination']);
			}

			$table = '<table>';
			$table .='<thead><tr>';
			foreach ($fields as $field)
			{
				$table .= '<th>' . str_replace('_',' ', Str::title($field)) . '</th>';
			}
			if ($show['Edit'] || $show['Delete'] || $show['View']){
				$table .= '<th>Actions</th>';
			}
			$table .= '</tr></thead>';

			foreach ( $data as $d )
			{
				$table .= '<tr>';
				foreach($fields as $key) {
					if($key != 'actions'){
						$value = null;
						if(is_object($d[$key])){
							$value = $d[$key]->name;
						}else{
							$value = $d[$key];
						}
						$table .= '<td data-title="'.str_replace('_',' ', Str::title($key)).'">' . $value . '</td>';
					}
				}
				if ($show['Edit'] || $show['Delete'] || $show['View'])
				{
					$table .= '<td data-title="Actions">';
					$showLink = false;
					if(isset($d['private'])){
						if($d['private'] == true) {
							$showLink = false;
						}else{
							$showLink = true;
						}
					}
					if(Auth::check() && isset($d['user_id'])){
						$showLink = Auth::user()->id == $d['user_id'];
					}
					if ($show['Edit']){
						$table .= $this->actionlink(array('action' => $show['Edit'] , 'params' => array($d['id'])), '<i class="fa fa-pencil"></i>Edit', array(), $showLink).' ';
					}
					if ($show['View']){
						$table .= $this->actionlink(array('action' => $show['View'], 'params' => array($d['id'])), '<i class="fa fa-eye"></i>View', array(), $showLink).' ';
					}
					if ($show['Delete']){
						$table .= $this->actionlink(array('action' => $show['Delete'], 'params' => array($d['id'])), '<i class="fa fa-trash-o"></i>Delete', array(), $showLink).' ';
					}
					$table .= '</td>';
				}
				$table .= '</tr>';
			}
			$table .= '</table>';

			$table .= $paginator->render();
			return $table;
		}else{
			return '<div class="text-center alert info">'.$info.'</div>';
		}
	}
}