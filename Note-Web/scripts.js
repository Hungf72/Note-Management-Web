document.addEventListener('DOMContentLoaded', () => {
    let allLabels = [];
    let currentLabelFilter = [];
    let isEditingLabels = false;
    let allNotes = []; // Store all notes for searching
    
    document.getElementById('searchNotes').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        let filteredNotes;

        if (!allNotes.length) return;

        if (searchTerm) {
            filteredNotes = allNotes.filter(note =>note.title.toLowerCase().includes(searchTerm) || note.content.toLowerCase().includes(searchTerm)
            );
        } else {
            filteredNotes = allNotes;
        }

        displayNotes(filteredNotes);
    });
    
    const email = localStorage.getItem("email");
    alert(email);
    if (!email) {
        setTimeout(() => {
            window.location.href = "login.html";
        }, 1000);
    }

    fetchNotes();
    //fetchLabels();

    document.getElementById("noteForm").addEventListener("submit", function (e) {
        e.preventDefault();
        const noteId = this.getAttribute("data-id");
        const title = document.getElementById("noteTitle").value.trim();
        const content = document.getElementById("noteContent").value.trim();

        if (!title || !content) {
            alert("Both title and content are required.");
            return;
        }

        if (noteId) {
            updateNote(noteId, title, content);
        } else {
            createNote(title, content);
        }
    });

    function fetchLabels() {
        fetch('/Note-Management-Web/Note-Web/controllers/labels.php', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                allLabels = data.labels;
                // displayLabels(allLabels);
            }
            else {
                console.error('Error fetching labels:', data.message);
            }
        })
        .catch(error => {
            console.error('Connection error:', error);
        });
    }


    function createNote(title, content) {
        if (!title) {
            alert('Title is required.');
            return;
        }
        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                title,
                content,
                action: 'create'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('noteTitle').value = '';
                document.getElementById('noteContent').value = '';
                fetchNotes();
            } else {
                alert('Error creating note: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    }

    function fetchNotes() {
        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'GET' 
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                allNotes = data.notes; // Store all notes for searching
                displayNotes(data.notes);
            } else {
                console.error('Lỗi khi lấy notes:', data.message);
                console.log('Dữ liệu trả về:', data);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    }

    function displayNotes(notes) {
        const notesList = document.getElementById('notesList');
        notesList.innerHTML = '';

        if (notes.length === 0) {
            notesList.innerHTML = '<p class="text-center">No notes found.</p>';
            return;
        }

        // Sort notes: pinned first, then by last modified date
        const sortedNotes = [...notes].sort((a, b) => {
            if (a.is_pinned !== b.is_pinned) {
                return b.is_pinned - a.is_pinned; // Pinned notes first
            }
            // For notes with same pin status, sort by last modified date
            return new Date(b.last_modified) - new Date(a.last_modified);
        });

        sortedNotes.forEach(note => {
            const pinIconClass = note.is_pinned == 1 ? 'bi bi-pin-angle-fill' : 'bi bi-pin-angle';
            const noteCard = document.createElement('div');
            noteCard.className = `card note-card mb-3 ${note.is_pinned == 1 ? 'pinned-note' : ''}`;
            noteCard.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">${note.title}</h5>
                        <i class="${pinIconClass} pin-icon" data-id="${note.id}" ></i>
                    </div>
                    <p class="card-text">${note.content || ''}</p>
                    <div class="d-flex align-items-center">
                        <small class="text-muted">Last modified: ${new Date(note.last_modified).toLocaleString()}</small>
                        <button class="btn btn-sm btn-primary edit-btn pl-3 pr-3 ml-3" data-id="${note.id}">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn pl-3 pr-3 ml-3" data-id="${note.id}">Delete</button>
                    </div>
                </div>
            `;
            notesList.appendChild(noteCard);
        });
    }

    function toggleNotePin(noteId) {
        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: noteId,
                action: 'togglePin'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                fetchNotes();       
            } else {
                alert('Error updating pin status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Connection error:', error);
        });
    }

    document.getElementById('notesList').addEventListener('click', function(e) {
            if (e.target.classList.contains('pin-icon')) {
                const noteId = e.target.getAttribute('data-id');
                toggleNotePin(noteId);
            }
        });     
            
    document.getElementById('notesList').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-btn')) {
            const noteId = e.target.getAttribute('data-id');
            deleteNote(noteId);
        }
    });

    document.getElementById('notesList').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('edit-btn')) {
            const noteId = e.target.getAttribute('data-id');
            editNote(noteId);
        }
    });   

    function deleteNote(noteId) {
        if (!confirm('Bạn muốn xóa note này?')) return;

        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: noteId,
                action: 'delete'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const form = document.getElementById('noteForm');
                form.removeAttribute('data-id');
                form.reset();
                form.querySelector('button[type="submit"]').textContent = 'Create Note';
                fetchNotes();
            } else {
                alert('Error deleting note: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    }

    function editNote(noteId) {
        fetch(`/Note-Management-Web/Note-Web/controllers/notes.php?id=${noteId}&action=edit`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('noteTitle').value = data.note.title;
                document.getElementById('noteContent').value = data.note.content;
                document.getElementById('noteFormBtn').textContent = 'Update Note';
                document.getElementById('noteFormHeader').textContent = 'Edit Note';
                document.getElementById("noteForm").setAttribute("data-id", noteId);
            } else {
                alert('Error fetching note: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    }

    function updateNote(noteId) {
        const title = document.getElementById('noteTitle').value.trim();
        const content = document.getElementById('noteContent').value.trim();
        if (!title) {
            alert('Title is required.');
            return;
        }
        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: noteId,
                title,
                content,
                action: 'autosave'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('noteTitle').value = '';
                document.getElementById('noteContent').value = '';
                document.getElementById('noteFormBtn').textContent = 'Create Note';
                fetchNotes();
            } else {
                alert('Error updating note: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    }
});





