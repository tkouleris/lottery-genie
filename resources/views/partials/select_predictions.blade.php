<div class="flex flex-col items-center gap-6">
    Select Number of Draws:
    <div class="flex flex-wrap justify-center gap-4">
        @foreach([1, 2, 3, 4, 5, 6] as $i)
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="radio" name="number_of_draws" value="{{ $i }}" {{ request('number_of_draws') == $i ? 'checked' : '' }} class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 focus:ring-blue-500">
                <span class="text-slate-300 group-hover:text-white transition-colors">{{ $i }}</span>
            </label>
        @endforeach
    </div>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded-full transition-all transform hover:scale-105">
        Predict
    </button>
</div>
