{{-- 多媒體上傳表單 --}}

<form action="{{ url('/media/upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="images[]" multiple accept="image/*">
    <button type="submit">上傳</button>
</form>
