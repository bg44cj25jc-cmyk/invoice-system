<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
</head>

<body>
@include('include/header')

@include('include/left')

<div class="page-wrapper">


    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <!--<div class="card-body customBody">
                        <h5 class="card-title mb-0">Language</h5>
                        <button type="button" class="btn btn-primary customBtn" onclick="window.location.href='{{ url('dashboard/create-language') }}'">
                            Add New
                        </button>
                    </div>-->

                    <div class="table-responsive">
                        @if(session()->has('success'))
                            <div class="alert alert-success">
                                {{ session()->get('success') }}
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </div>
                        @endif
                        <table class="table">
                            <thead class="thead-light">
                            <tr>
                                <th scope="col">Sl No</th>
                                <th scope="col">Key</th>
								<th scope="col">en_language</th>
								<th scope="col">is_language</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
								<tbody class="customtable">
								@if(is_array($data) && !empty($data))
									@foreach($data as $index => $v)
									<tr>
										<td>#{{ $index + 1 }}</td>
										<td>{{ $v['origin_text'] }}</td>
										<td>
											<input type="text" class="form-control en_language" value="{{ $v['en_language'] }}" data-id="{{ $v['id'] }}">
										</td>
										<td>
											<input type="text" class="form-control is_language" value="{{ $v['is_language'] }}" data-id="{{ $v['id'] }}">
										</td>
										<td>
											<button class="btn btn-sm btn-success updateLangBtn" data-id="{{ $v['id'] }}">Update</button>
										</td>
									</tr>
									@endforeach
								@endif
								</tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

</div>
@include('include/footer')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on('click', '.updateLangBtn', function() {
    const id = $(this).data('id');
    const row = $(this).closest('tr'); // ✅ define the row here
    const en_language = $('input.en_language[data-id="'+id+'"]').val();
    const is_language = $('input.is_language[data-id="'+id+'"]').val();

    $.ajax({
        url: "{{ url('dashboard/edit-language') }}/" + id,
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: id,
            en_language: en_language,
            is_language: is_language
        },
        success: function(response) {
            if (response.res) {
                // ✅ Add green highlight with fade effect
                row.css('background-color', '#d4edda'); // light green
                setTimeout(() => {
                    row.css('transition', 'background-color 1s ease');
                    row.css('background-color', '');
                }, 800);

                console.log('✅ ' + response.msg);
            } else {
                alert('❌ Update failed');
            }
        },
        error: function() {
            alert('⚠️ Error updating language.');
        }
    });
});
</script>


</body>
</html>
