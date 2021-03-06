<?php namespace App;

class Tag extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'tags';

	protected $fillable = array('name');

	protected $guarded = array('id');

	public static $rules = array(
	    'name' => 'required|min:3|unique:tags,name,:id:',
	);

	public function Posts() {
		return $this->belongsToMany('App\Post','post_tag');
	}

}