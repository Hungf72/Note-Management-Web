document.addEventListener('DOMContentLoaded', () => {
    let allLabels = [];
    let allNotes = []; // Store all notes for searching
    let isEditingLabels = false;

    const sidebarHome = document.getElementById('sidebarHome');
    const sidebarNotes = document.getElementById('sidebarNotes');
    const homeContent = document.querySelector('.Home-Content');
    const noteContent = document.querySelector('.Note-Content');

    homeContent.style.display = 'block';
    noteContent.style.display = 'none';

    sidebarHome.addEventListener('click', (e) => {
        e.preventDefault();
        homeContent.style.display = 'block';
        noteContent.style.display = 'none';
        
        sidebarNotes.classList.remove('active');
        sidebarHome.classList.add('active');
    });

    sidebarNotes.addEventListener('click', (e) => {
        e.preventDefault();
        homeContent.style.display = 'none';
        noteContent.style.display = 'block';
        
        sidebarHome.classList.remove('active');
        sidebarNotes.classList.add('active');

        fetchNotes(); 
        fetchLabelsAndUpdateSelect(); 
    });
    
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    
    document.getElementById('searchNotes').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        let filteredNotes;

        if (!allNotes.length) return;

        if (searchTerm) {
            filteredNotes = allNotes.filter(note =>note.title.toLowerCase().includes(searchTerm) || note.content.toLowerCase().includes(searchTerm) || (note.labels && note.labels.some(labelId => {
                const label = allLabels.find(label => label.id == labelId);
                return label && label.name.toLowerCase().includes(searchTerm);
            }))
            );
        } else {
            filteredNotes = allNotes;
        }

        displayNotes(filteredNotes);
    });
    
    fetchNotes();
    fetchLabels();

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


    const labelsList = document.getElementById('labelsList');
    const addLabelBtn = document.getElementById('addLabelBtn');
    const addLabelInputGroup = document.getElementById('addLabelInputGroup');
    const newLabelName = document.getElementById('newLabelName');
    const saveNewLabelBtn = document.getElementById('saveNewLabelBtn');
    const cancelNewLabelBtn = document.getElementById('cancelNewLabelBtn');
    const noteUI = document.getElementById('Note-Content');

    function displayLabels(labels) {
        labelsList.innerHTML = '';
        labels.forEach(label => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                <span class="label-name" data-id="${label.id}">${label.name}</span>
                <div>
                    <button class="btn btn-sm btn-outline-primary editLabelBtn" data-id="${label.id}" data-name="${label.name}"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger deleteLabelBtn" data-id="${label.id}"><i class="fas fa-trash"></i></button>
                </div>
            `;
            labelsList.appendChild(li);
        });
    }

    function refreshLabels() {
        fetchLabels();
    }

    addLabelBtn.addEventListener('click', () => {
        addLabelInputGroup.style.display = 'flex';
        newLabelName.value = '';
        newLabelName.focus();
    });

    cancelNewLabelBtn.addEventListener('click', () => {
        addLabelInputGroup.style.display = 'none';
        newLabelName.value = '';
    });

    document.getElementById('sidebarNotes').addEventListener('click', function(e) {
        noteUI.style.display = 'flex';
    });

    saveNewLabelBtn.addEventListener('click', () => {
        if (isEditingLabels) return; // Prevent creating a new label while editing

        const name = newLabelName.value.trim();
        if (!name) return;
        fetch('/Note-Management-Web/Note-Web/controllers/labels.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded' 
            },
            body: new URLSearchParams({ 
                name, 
                action: 'create' 
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                addLabelInputGroup.style.display = 'none';
                newLabelName.value = '';
                refreshLabels();
                fetchNotes();
            } else {
                alert(data.message);
            }
        });
    });

    labelsList.addEventListener('click', function(e) {
        if (e.target.closest('.editLabelBtn')) {
            const btn = e.target.closest('.editLabelBtn');
            const id = btn.getAttribute('data-id');
            const oldName = btn.getAttribute('data-name');
            editLabel(id, oldName);
        }
        else if (e.target.closest('.deleteLabelBtn')) {
            const btn = e.target.closest('.deleteLabelBtn');
            const id = btn.getAttribute('data-id');
            deleteLabel(id);
        }
    });
                

    function deleteLabel(labelId) {
        if (confirm('Delete this label?')) {
            fetch('/Note-Management-Web/Note-Web/controllers/labels.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded' 
                },
                body: new URLSearchParams({ 
                    id: labelId, 
                    action: 'delete' 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success'){
                    refreshLabels();
                    fetchNotes();
                }
                else{
                    alert(data.message);
                }
            });
        }
    }

    function editLabel(labelId, oldName) {
        addLabelInputGroup.style.display = 'flex';
        newLabelName.value = oldName;
        newLabelName.focus();
        isEditingLabels = true;
        saveNewLabelBtn.onclick = function() {
            renameLabel(labelId, oldName);
        };
    }

    function renameLabel(labelId, oldName) {
        const newName = newLabelName.value.trim();
        if (newName && newName.trim() && newName !== oldName) {
            fetch('/Note-Management-Web/Note-Web/controllers/labels.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    id: labelId, 
                    name: newName.trim(), 
                    action: 'update' 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success'){
                    refreshLabels();
                    isEditingLabels = false;
                    saveNewLabelBtn.onclick = null; // Reset the click handler
                    addLabelInputGroup.style.display = 'none';
                    newLabelName.value = '';
                }
                else{
                    alert(data.message);
                }
            })
            .catch(err => console.error('Error renaming label:', err));
        }
    }


    // Fetch and show labels on load
    function fetchLabels() {
        fetch('/Note-Management-Web/Note-Web/controllers/labels.php', { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                allLabels = data.labels;
                displayLabels(allLabels);
            }
        });
    }

    function createNote(title, content) {
        if (!title) {
            alert('Title is required.');
            return;
        }
        const selectedLabels = Array.from(noteLabelsSelect.selectedOptions).map(opt => opt.value);
        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                title,
                content,
                labels: selectedLabels.join(','),
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
            console.error('Connection error:', error);
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
                fetchLabelsAndUpdateSelect(); // Ensure labels are updated
                displayNotes(data.notes);
            } else {
                console.error('Note fetching error:', data.message);
            }
        })
        .catch(error => {
            console.error('Connection error:', error);
        });
    }

    const noteLabelsSelect = document.getElementById('noteLabels');

    function updateNoteLabelsSelect() {
        noteLabelsSelect.innerHTML = '';
        allLabels.forEach(label => {
            const option = document.createElement('option');
            option.value = label.id;
            option.textContent = label.name;
            noteLabelsSelect.appendChild(option);
        });
    }

    // Update label select when labels change
    function fetchLabelsAndUpdateSelect() {
        fetchLabels();
        updateNoteLabelsSelect();
    }

    // When creating/editing a note, set selected labels
    function setSelectedLabels(labelIds) {
        Array.from(noteLabelsSelect.options).forEach(opt => {
            opt.selected = labelIds && labelIds.includes(parseInt(opt.value));
        });
    }

    // When submitting note form, collect selected labels
    document.getElementById("noteForm").addEventListener("submit", function (e) {
        const selectedLabels = Array.from(noteLabelsSelect.selectedOptions).map(opt => opt.value);
        this.setAttribute('data-labels', selectedLabels.join(','));
    });

    // When displaying notes, show their labels
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
            // Show labels as badges
            let labelBadges = '';
            if (note.labels && note.labels.length) {
                labelBadges = note.labels.map(labelid => {
                    const label = allLabels.find(label => label.id == labelid);
                    return label ? `<span class='badge badge-info mr-1'>${label.name}</span>` : '';
                }).join('');
            }
            noteCard.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">${note.title}</h5>
                        <i class="${pinIconClass} pin-icon" data-id="${note.id}" ></i>
                    </div>
                    <div>${labelBadges}</div>
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

    addLabelBtn.addEventListener('click', updateNoteLabelsSelect);
    saveNewLabelBtn.addEventListener('click', fetchLabelsAndUpdateSelect);

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
        if (!confirm('Are you sure you want to delete this note?')) return;

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
            console.error('Connection error:', error);
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
                fetchLabelsAndUpdateSelect();
                setSelectedLabels(data.note.labels ? data.note.labels.map(Number) : []);
                document.getElementById("noteForm").setAttribute("data-id", noteId);
            } else {
                alert('Error fetching note: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Connection error:', error);
        });
    }

    function updateNote(noteId) {
        const title = document.getElementById('noteTitle').value.trim();
        const content = document.getElementById('noteContent').value.trim();
        if (!title) {
            alert('Title is required.');
            return;
        }
        const selectedLabels = Array.from(noteLabelsSelect.selectedOptions).map(opt => opt.value);
        fetch('/Note-Management-Web/Note-Web/controllers/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: noteId,
                title,
                content,
                labels: selectedLabels.join(','),
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
            console.error('Connection error:', error);
        });
    }
});







