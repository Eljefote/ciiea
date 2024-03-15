<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ConvocationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\InvestigationController;
use App\Http\Controllers\Admin\MagazineController;
use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use App\Models\Book;
use App\Models\Convocation;
use App\Models\Event;
use App\Models\Investigation;
use App\Models\Magazine;
use App\Models\Publication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
	return Inertia::render('Home', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
	]);
})->name('home');

Route::get('/divulgation', function () {
	return Inertia::render('Divulgation', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
	]);
})->name('divulgation');

Route::get('/books', function () {
	return Inertia::render('Resources/Books/Index', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'books' => Book::with('file')
			->select('id', 'title', 'authors', 'isbn', 'publicated_at', 'slug')
			->get()
			->each(function ($book, $index) {
				$book->publicated_at = Carbon::createFromDate($book->publicated_at)->isoFormat('LL');
			})
			->toArray(),
	]);
})->name('books');

Route::get('/magazines', function () {
	return Inertia::render('Resources/Magazines/Index', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'magazines' => Magazine::with('file')
			->select('name', 'publicated_at', 'slug')
			->get()
			->toArray(),
	]);
})->name('magazines');

Route::get('/hist-publications', function () {
	return Inertia::render('Resources/Publications/Index', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'publications' => Publication::with('file')
			->select('title', 'publicated_at', 'slug')
			->get()
			->toArray(),
	]);
})->name('hist-publications');

Route::get('/investigations', function () {
	return Inertia::render('Resources/Investigations/Index', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'investigations' => Investigation::with('file')
			->select('title', 'publicated_at', 'short_description', 'slug')
			->get()
			->toArray(),
	]);
})->name('investigations');

Route::get('/convocations', function () {
	return Inertia::render('Convocations/Index', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'convocations' => Convocation::select('id', 'name', 'date_time', 'location', 'description', 'slug', 'created_at')
			->with('image')
			->get()
			->each(function ($convocation, $index) {
				$date_time = Carbon::create($convocation->date_time);
				$convocation->date = $date_time->isoFormat('LL');
				$convocation->time = $date_time->isoFormat('h:mm');
				$convocation->created_at_for_humans = Carbon::createFromTimestamp($convocation->created_at)->diffForHumans();
			})
			->toArray(),
	]);
})->name('convocations.index');

Route::get('/convocations/{slug}', function ($slug) {
	$convocation = Convocation::where('slug', $slug)->first()->toArray();

	$date_time = Carbon::createFromDate($convocation['date_time']);

	$convocation['date'] = $date_time->isoFormat('LL');

	$convocation['time'] = $date_time->format('g:i a');

	return Inertia::render('Convocations/Show', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'convocation' => $convocation,
	]);
})->name('convocations.show');

Route::get('/gallery', function () {
	// $events = Event::with('images')->get();
	// dd($events);
	return Inertia::render('Gallery/Index', [
		'events' => Event::with('images')->get(),
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
	]);
})->name('gallery.index');

Route::get('/gallery/{event}', function ($event) {
	$event = Event::where('id', $event)->first();

	$images = $event->images;

	return Inertia::render('Gallery/Details', [
		'images' => $images,
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
	]);
})->name('gallery.details');

Route::get('/reime', function () {
	return Inertia::render('Reime/Index', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
	]);
})->name('reime');

Route::get('/contact', function () {

	// dd(User::get()->toArray());
	return Inertia::render('Contact', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'administrators' => User::role('admin')->get()->toArray(),
	]);
})->name('contact');

Route::get('/files/{file}', [FileController::class, 'show'])->name('file.show');

Route::get('/dashboard', function () {
	return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])
	->name('dashboard');

Route::middleware('auth')->group(function () {
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin'])
	->prefix('admin')
	->group(function () {
		Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

		Route::get('/books', [BookController::class, 'index'])->name('admin.books.index');
		Route::post('/books', [BookController::class, 'store'])->name('admin.books.store');
		Route::get('/books/{id}/edit', [BookController::class, 'edit'])->name('admin.books.edit');
		Route::patch('/books', [BookController::class, 'update'])->name('admin.books.update');
		Route::delete('/books', [BookController::class, 'destroy'])->name('admin.books.destroy');
		Route::post('/books/file', [BookController::class, 'uploadFile'])->name('admin.books.upload-file');
		Route::delete('/book/file', [BookController::class, 'deleteFile'])->name('admin.books.delete-file');

		Route::get('/magazines', [MagazineController::class, 'index'])->name('admin.magazines.index');
		Route::post('/magazines', [MagazineController::class, 'store'])->name('admin.magazines.store');
		Route::get('/magazines/{id}/edit', [MagazineController::class, 'edit'])->name('admin.magazines.edit');
		Route::patch('/magazines', [MagazineController::class, 'update'])->name('admin.magazines.update');
		Route::delete('/magazines', [MagazineController::class, 'destroy'])->name('admin.magazines.destroy');
		Route::post('/magazines/file', [MagazineController::class, 'uploadFile'])->name('admin.magazines.upload-file');
		Route::delete('/magazines/file', [MagazineController::class, 'deleteFile'])->name('admin.magazines.delete-file');

		Route::get('/historical-publications', [PublicationController::class, 'index'])->name('admin.historical-publications.index');
		Route::post('/historical-publications', [PublicationController::class, 'store'])->name('admin.historical-publications.store');
		Route::get('/historical-publications/{id}/edit', [PublicationController::class, 'edit'])->name('admin.historical-publications.edit');
		Route::patch('/historical-publications', [PublicationController::class, 'update'])->name('admin.historical-publications.update');
		Route::delete('/historical-publications', [PublicationController::class, 'destroy'])->name('admin.historical-publications.destroy');
		Route::post('/historical-publications/file', [PublicationController::class, 'uploadFile'])->name('admin.historical-publications.upload-file');
		Route::delete('/historical-publications/file', [PublicationController::class, 'deleteFile'])->name('admin.historical-publications.delete-file');

		Route::get('/investigations', [InvestigationController::class, 'index'])->name('admin.investigations.index');
		Route::post('/investigations', [InvestigationController::class, 'store'])->name('admin.investigations.store');
		Route::get('/investigations/{id}/edit', [InvestigationController::class, 'edit'])->name('admin.investigations.edit');
		Route::patch('/investigations', [InvestigationController::class, 'update'])->name('admin.investigations.update');
		Route::delete('/investigations', [InvestigationController::class, 'destroy'])->name('admin.investigations.destroy');
		Route::post('/investigations/file', [InvestigationController::class, 'uploadFile'])->name('admin.investigations.upload-file');
		Route::delete('/investigations/file', [InvestigationController::class, 'deleteFile'])->name('admin.investigations.delete-file');

		Route::get('/convocations', [ConvocationController::class, 'index'])->name('admin.convocations.index');
		Route::post('/convocations', [ConvocationController::class, 'store'])->name('admin.convocations.store');
		Route::get('/convocations/{id}/edit', [ConvocationController::class, 'edit'])->name('admin.convocations.edit');
		Route::patch('/convocations', [ConvocationController::class, 'update'])->name('admin.convocations.update');
		Route::delete('/convocations', [ConvocationController::class, 'destroy'])->name('admin.convocations.destroy');
		Route::post('/convocations/image', [ConvocationController::class, 'uploadImage'])->name('admin.convocations.upload-image');
		Route::delete('/convocations/image', [ConvocationController::class, 'deleteImage'])->name('admin.convocations.delete-image');

		Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery.index');
		Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.gallery.store');
		Route::get('/gallery/{id}/edit', [GalleryController::class, 'edit'])->name('admin.gallery.edit');
		Route::delete('/gallery', [GalleryController::class, 'destroy'])->name('admin.gallery.destroy');
		Route::post('/gallery/images', [GalleryController::class, 'uploadImages'])->name('admin.gallery.upload-images');
		Route::delete('/gallery/images', [GalleryController::class, 'deleteImages'])->name('admin.gallery.delete-images');

		Route::get('/administrators', [AdministratorController::class, 'index'])->name('admin.administrators.index');
		Route::post('/administrators', [AdministratorController::class, 'store'])->name('admin.administrators.store');
		Route::get('/administrators/{id}/edit', [AdministratorController::class, 'edit'])->name('admin.administrators.edit');
		Route::patch('/administrators', [AdministratorController::class, 'update'])->name('admin.administrators.update');
		Route::delete('/administrators', [AdministratorController::class, 'destroy'])->name('admin.administrators.destroy');

		Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index');
		Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('admin.roles.edit');

		Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
		Route::delete('/users', [UserController::class, 'destroy'])->name('admin.users.destroy');

		Route::get('/contact', function () {
			return Inertia::render('Admin/Contact', [
				'users' => [
					[
						'name' => 'danie',
						'position' => 'director general',
						'celular_number' => '9321123242',
						'email' => 'admin@example.com',
						'twitter' => '@dir_ciiea',
					],
					[
						'name' => 'carlos',
						'position' => 'sistemas',
						'celular_number' => '9321132242',
						'email' => 'sistemas@example.com',
						'twitter' => '@sistemas_ciiea',
					]
				]
			]);
		})->name('admin.contact');

		Route::post('/contact', [ContactController::class, 'update'])->name('admin.contact.update');
	});

require __DIR__ . '/auth.php';
