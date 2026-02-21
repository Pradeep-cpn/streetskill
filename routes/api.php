use App\Models\User;
use App\Models\SwapRequest;
use Illuminate\Http\Request;

Route::post('/login', function (Request $request) {
    if (!auth()->attempt($request->only('email','password'))) {
        return response()->json(['error' => 'Invalid'], 401);
    }

    $token = $request->user()->createToken('api-token')->plainTextToken;

    return response()->json(['token' => $token]);
});

Route::middleware('auth:sanctum')->get('/marketplace', function () {
    return User::all();
});
