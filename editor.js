let state={q:'',page:1,perPage:15,lastItems:[]};
const viewModal=new bootstrap.Modal('#viewModal');
const editModal=new bootstrap.Modal('#editModal');

function escapeHtml(s){return $('<div>').text(s==null?'':s).html();}

function renderRows(items){
    const tbody=$('#rows').empty();
    state.lastItems=items||[];
    for(var i=0;i<state.lastItems.length;i++){
        var it=state.lastItems[i];
        var cover=it.cover_file||it.movie_image||it.cover_url||'';
        tbody.append(
            '<tr>'+
            '<td>'+it.id+'</td>'+
            '<td>'+(cover?('<img src="'+escapeHtml(cover)+'" class="thumb">'):'')+'</td>'+
            '<td>'+escapeHtml(it.title||'')+'</td>'+
            '<td class="small">'+escapeHtml(it.path||'')+'</td>'+
            // '<td class="truncate-2">'+escapeHtml(it.description||'')+'</td>'+
            '<td class="text-end">'+
            '<div class="btn-group" role="group" aria-label="Basic example">'+
            '<button class="btn btn-sm btn-outline-secondary btn-view" data-id="'+it.id+'"><i class="bi bi-info-lg"></i> View</button>'+
            '<button class="btn btn-sm btn-primary btn-edit" data-id="'+it.id+'"><i class="bi bi-pencil-fill"></i> Edit</button>'+
            '</div>'+
            '</td>'+
            '</tr>'
        );
    }
}

function renderPager(total,page,perPage){
    const pages=Math.max(1,Math.ceil(total/perPage));
    const ul=$('#pager').empty();
    const add=function(p,label,dis,act){
        ul.append('<li class="page-item '+(dis?'disabled':'')+' '+(act?'active':'')+'">'+
            '<a class="page-link" href="#" data-page="'+p+'">'+label+'</a></li>');
    };
    add(Math.max(1,page-1),'&laquo;',page<=1,false);
    for(var p=1;p<=pages&&p<=50;p++){ add(p,''+p,false,p===page); }
    add(Math.min(pages,page+1),'&raquo;',page>=pages,false);
}

function loadList(){
    $.getJSON('editor.php',{action:'list',q:state.q,page:state.page,perPage:state.perPage},function(res){
        if(!res||!res.ok)return;
        renderRows(res.items); renderPager(res.total,res.page,res.perPage);
    });
}

function openView(id){
    $.getJSON('editor.php',{action:'get',id:id},function(res){
        if(!res||!res.ok||!res.item)return;
        var v=res.item;
        var cover=v.cover_file||v.movie_image||v.cover_url||'';
        $('#viewContent').html(
            '<div class="row g-3">'+
            '<div class="col-md-4">'+
            (cover?('<img src="'+escapeHtml(cover)+'" class="view-cover">'):'<div class="text-muted">No image</div>')+
            '</div>'+
            '<div class="col-md-8">'+
            '<h5 class="mb-1">'+escapeHtml(v.title||'')+'</h5>'+
            '<div class="text-muted mb-2">'+escapeHtml(v.year||'')+'</div>'+
            '<div class="mb-2"><strong>Path:</strong> <span class="small text-break">'+escapeHtml(v.path||'')+'</span></div>'+
            '<div class="mb-2"><strong>Source URL:</strong> '+(v.source_url?('<a href="'+escapeHtml(v.source_url)+'" target="_blank" rel="noopener">Open</a>'):'<span class="text-muted">—</span>')+'</div>'+
            '<div class="mb-2"><strong>Cover URL:</strong> '+(v.cover_url?('<a href="'+escapeHtml(v.cover_url)+'" target="_blank" rel="noopener">Open</a>'):'<span class="text-muted">—</span>')+'</div>'+
            '<div class="mt-3"><strong>Description:</strong>'+
            '<pre class="wrap-pre">'+escapeHtml(v.description||'')+'</pre>'+
            '</div>'+
            '</div>'+
            '</div>'
        );
        $('#viewToEdit').off('click').on('click',function(){ viewModal.hide(); openEdit(v.id); });
        viewModal.show();
    });
}

function openEdit(id){
    if(id){
        $.getJSON('editor.php',{action:'get',id:id},function(res){
            if(!res||!res.ok)return;
            fillEdit(res.item); editModal.show();
        });
    }else{
        fillEdit({}); editModal.show();
    }
}

function fillEdit(v){
    // Keep path value in hidden field so it is saved on create, not on update
    var pathVal = v.path||'';
    var cover = v.cover_file||'';
    var movie = v.movie_image||'';
    var html =
        '<input type="hidden" name="id" value="'+(v.id||'')+'">'+
        '<div class="row g-3">'+

        '<div class="col-md-6">'+
        '<label class="form-label">Title</label>'+
        '<input class="form-control" name="title" value="'+escapeHtml(v.title||'')+'">'+
        '</div>'+

        '<div class="col-md-6">'+
        '<label class="form-label">Year</label>'+
        '<input class="form-control" name="year" value="'+escapeHtml(v.year||'')+'">'+
        '</div>'+

        '<div class="col-12">'+
        '<label class="form-label">Path</label>'+
        '<input class="form-control" value="'+escapeHtml(pathVal)+'" disabled>'+
        '<input type="hidden" name="path" value="'+escapeHtml(pathVal)+'">'+
        '</div>'+

        '<div class="col-12">'+
        '<label class="form-label">Description</label>'+
        '<textarea class="form-control" rows="4" name="description">'+escapeHtml(v.description||'')+'</textarea>'+
        '</div>'+

        '<div class="col-md-6">'+
        '<label class="form-label">Cover URL</label>'+
        '<input class="form-control" name="cover_url" value="'+escapeHtml(v.cover_url||'')+'">'+
        '</div>'+

        '<div class="col-md-6">'+
        '<label class="form-label">Source URL</label>'+
        '<input class="form-control" name="source_url" value="'+escapeHtml(v.source_url||'')+'">'+
        '</div>'+

        '<div class="col-md-6">'+
        '<label class="form-label">Cover File</label>'+
        '<input type="file" class="form-control" name="cover_file" accept="image/*">'+
        '<input type="hidden" name="cover_file_keep" value="'+escapeHtml(cover)+'">'+
        (cover?('<img src="'+escapeHtml(cover)+'" class="edit-preview" alt="Cover preview">'):'')+
        '</div>'+

        '<div class="col-md-6">'+
        '<label class="form-label">Movie Image</label>'+
        '<input type="file" class="form-control" name="movie_image" accept="image/*">'+
        '<input type="hidden" name="movie_image_keep" value="'+escapeHtml(movie)+'">'+
        (movie?('<img src="'+escapeHtml(movie)+'" class="edit-preview" alt="Movie image preview">'):'')+
        '</div>'+

        '</div>';
    $('#editFormFields').html(html);
}

$(document).on('click','.btn-view',function(e){e.preventDefault(); openView($(this).data('id'));});
$(document).on('click','.btn-edit',function(e){e.preventDefault(); openEdit($(this).data('id'));});
$('#btnNew').on('click',function(){ openEdit(null); });
$('#btnSearch').on('click',function(){ state.q=$('#search').val(); state.page=1; loadList(); });
$('#btnReset').on('click',function(){ $('#search').val(''); state.q=''; state.page=1; loadList(); });
$('#perPage').on('change',function(){ state.perPage=parseInt($('#perPage').val(),10)||15; state.page=1; loadList(); });
$('#pager').on('click','.page-link',function(e){ e.preventDefault(); var p=parseInt($(this).data('page'),10); if(!isNaN(p)){ state.page=p; loadList(); } });

$('#editForm').on('submit',function(e){
    e.preventDefault();
    if(!confirm('Are you sure?')) return;
    var fd=new FormData(this);
    $.ajax({
        url:'editor.php?action=save',
        method:'POST',
        data:fd, processData:false, contentType:false,
        success:function(res){
            if(res && res.ok){ editModal.hide(); loadList(); }
            else{ alert('Save error'); }
        },
        error:function(){ alert('Network / server error'); }
    });
});

$(function(){
    state.perPage=parseInt($('#perPage').val(),10)||15;
    loadList();
});
