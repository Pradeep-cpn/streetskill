use App\Http\Controllers\ApiController;

Route::post('/login', [ApiController::class, 'login']);
Route::middleware('auth:sanctum')->get('/marketplace', [ApiController::class, 'marketplace']);
