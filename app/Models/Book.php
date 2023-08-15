<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Book extends Model
{
	use HasFactory;

	protected $fillable = [
		'uuid',
		'title',
		'authors',
		'isbn',
		'publication_date',
	];

	public function file(): MorphOne
	{
		return $this->morphOne(File::class, 'fileable');
	}
}
