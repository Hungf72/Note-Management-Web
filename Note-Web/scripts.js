document.addEventListener('DOMContentLoaded', () => {
    fetchNotes();

    const email = localStorage.getItem("email");
    alert(email);
    if (!email) {
        setTimeout(() => {
            window.location.href = "login.html";
        }, 1000);
    }

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

        notes.forEach(note => {
            const pinIconClass = note.is_pinned == 1 ? 'bi bi-pin-angle' : 'bi bi-pin-angle-fill';
                    
            const noteCard = document.createElement('div');
            noteCard.className = `card note-card mb-3 ${note.is_pinned ? 'pinned-note' : ''}`;
                    
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

    fetch('/Note-Management-Web/Note-Web/controllers/getUser.php', {
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const userName = document.getElementById('userName');
            userName.textContent = data.firstName + ' ' + data.lastName;
            // Set avatar for sidebar and popup
            let avatarUrl = data.avatar;
            if (avatarUrl) {
                if (!avatarUrl.startsWith('/')) avatarUrl = '/' + avatarUrl;
                avatarUrl = avatarUrl.replace(/\\/g, '/');
                if (!avatarUrl.startsWith('/Note-Management-Web/Note-Web/images/avatars/')) {
                    avatarUrl = '/Note-Management-Web/Note-Web/' + avatarUrl.replace(/^\/*/, '');
                }
            } else {
                avatarUrl = '/Note-Management-Web/Note-Web/images/personal.png';
            }
            document.getElementById('userInfo').src = avatarUrl;
            document.getElementById('popupUserInfo').src = avatarUrl;
        } else {
            const userName = document.getElementById('userName');
            userName.textContent = 'Guest';
            document.getElementById('userInfo').src = '/Note-Management-Web/Note-Web/images/personal.png';
            document.getElementById('popupUserInfo').src = '/Note-Management-Web/Note-Web/images/personal.png';
        }
    })
    .catch(error => {
        console.error('Lỗi khi lấy user:', error);
    });

    // active account
    fetch(`/Note-Management-Web/Note-Web/controllers/checkActivate.php?email=${encodeURIComponent(localStorage.getItem('email'))}`)
    .then(res => res.json())
    .then(data => {
        const msgBox = document.getElementById('activationMsg');
        if (data.status === 'success' && !data.is_active) {
            const link = localStorage.getItem('activation_link');
            msgBox.innerHTML = `Tài khoản của bạn chưa được kích hoạt. Nhấn <a href="${link}" target="_blank">vào đây để kích hoạt</a>.`;
            msgBox.style.display = 'block';
        } 
        else {
            msgBox.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Lỗi khi kiểm tra kích hoạt:', error);
    });

});

// khi an logout remove localStorage
document.getElementById("logOut").addEventListener("click", function(e) {
    e.preventDefault();

    localStorage.removeItem("email");
    localStorage.removeItem("is_activated");

    const userName = document.getElementById("userName")

    if (userName) {
        userName.textContent = "Guest";
    }

    window.location.href = "login.html"; 
});

//close pop up
document.getElementById('popup-card').addEventListener('click', (e) => {
    if (e.target.id === 'popup-card') {
        e.target.style.display = 'none';

        document.getElementById('firstNameUser').readOnly = true;
        document.getElementById('lastNameUser').readOnly = true;
        document.getElementById('ageUser').readOnly = true;
        document.getElementById('phoneUser').readOnly = true;
    }
});

// show information
document.getElementById("userInfo").addEventListener("click", () => {
    document.getElementById('popup-card').style.display = 'flex';
    fetch('/Note-Management-Web/Note-Web/controllers/getUser.php', {
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('emailUser').value = data.email;
            document.getElementById('firstNameUser').value = data.firstName;
            document.getElementById('lastNameUser').value = data.lastName;
            document.getElementById('ageUser').value = data.age;
            document.getElementById('phoneUser').value = data.phone;
        }
    })
    .catch(error => {
        document.getElementById('popup-card').style.display = 'flex';
    });
});

document.getElementById('editBtn').addEventListener('click', () =>{
    ['firstNameUser', 'lastNameUser', 'ageUser', 'phoneUser'].forEach(id => {
        document.getElementById(id).readOnly = false;
    });
});

// update information
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const popupAvatar = document.getElementById('popupUserInfo');
    const sidebarAvatar = document.getElementById('userInfo');
    const popupAvatarInput = document.getElementById('popupAvatarInput');
    let avatarChanged = false;
    if (popupAvatarInput.files && popupAvatarInput.files[0]) {
        formData.append('avatar', popupAvatarInput.files[0]);
        avatarChanged = true;
    }

    fetch('/Note-Management-Web/Note-Web/controllers/updateUser.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("modalMessage").textContent = data.message;
        $('#message-dialog').modal('show');

        if (data.status === "success") {
            fetch('/Note-Management-Web/Note-Web/controllers/getUser.php', {
                credentials: 'include'
            })
            .then(res => res.json())
            .then(user => {
                if (user.status === 'success') {
                    document.getElementById('emailUser').value = user.email;
                    document.getElementById('firstNameUser').value = user.firstName;
                    document.getElementById('lastNameUser').value = user.lastName;
                    document.getElementById('ageUser').value = user.age;
                    document.getElementById('phoneUser').value = user.phone;

                    const userName = document.getElementById('userName');
                    userName.textContent = user.firstName + ' ' + user.lastName;

                    // Update avatar everywhere after save
                    let avatarUrl = user.avatar;
                    if (avatarUrl) {
                        if (!avatarUrl.startsWith('/')) avatarUrl = '/' + avatarUrl;
                        avatarUrl = avatarUrl.replace(/\\/g, '/');
                        if (!avatarUrl.startsWith('/Note-Management-Web/Note-Web/images/avatars/')) {
                            avatarUrl = '/Note-Management-Web/Note-Web/' + avatarUrl.replace(/^\/*/, '');
                        }
                    } else {
                        avatarUrl = '/Note-Management-Web/Note-Web/images/personal.png';
                    }
                    document.getElementById('userInfo').src = avatarUrl;
                    document.getElementById('popupUserInfo').src = avatarUrl;
                }
                
                ['firstNameUser', 'lastNameUser', 'ageUser', 'phoneUser'].forEach(id => {
                    document.getElementById(id).readOnly = true;
                });

                document.getElementById('popup-card').style.display = 'none';
            });
        }
    })
    .catch(err => {
        console.error('Lỗi khi cập nhật:', err);
    });
});

// avatar change
document.getElementById('popupAvatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) {
        return;
    }

    const reader = new FileReader();
    reader.onload = function(evt) {
        document.getElementById('popupUserInfo').src = evt.target.result;
    };

    reader.readAsDataURL(file);
});

// Xử lý submit form đổi mật khẩu
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
    if (form) {
        form.onsubmit = function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value.trim();
            const newPassword = document.getElementById('newPassword').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();
            const msgBox = document.getElementById('changePwdMsg');
            msgBox.textContent = '';
            if (!currentPassword || !newPassword || !confirmPassword) {
                msgBox.textContent = 'Vui lòng nhập đầy đủ thông tin.';
                msgBox.style.color = 'red';
                return;
            }
            if (newPassword !== confirmPassword) {
                msgBox.textContent = 'Mật khẩu mới không khớp.';
                msgBox.style.color = 'red';
                return;
            }
            fetch('/Note-Management-Web/Note-Web/controllers/updatepwd.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    currentPassword,
                    newPassword,
                    action: 'changePassword',
                    email: localStorage.getItem('email')
                })
            })
            .then(res => res.json())
            .then(data => {
                msgBox.textContent = data.message;
                msgBox.style.color = data.status === 'success' ? 'green' : 'red';
                if (data.status === 'success') {
                    form.reset();
                }
            })
            .catch(() => {
                msgBox.textContent = 'Có lỗi xảy ra, vui lòng thử lại.';
                msgBox.style.color = 'red';
            });
        };
    }
});

document.getElementById("toggleCurrentPassword").addEventListener("click", function () {
    const pwd = document.getElementById("currentPassword");
    const icon = document.getElementById("toggleCurrentIcon");
    pwd.type = pwd.type === "password" ? "text" : "password";
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
});

document.getElementById("togglePassword").addEventListener("click", function () {
    const pwd = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");
    pwd.type = pwd.type === "password" ? "text" : "password";
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
});

document.getElementById("toggleConfirmPassword").addEventListener("click", function () {
    const pwd = document.getElementById("confirmPassword");
    const icon = document.getElementById("toggleConfirmIcon");
    pwd.type = pwd.type === "password" ? "text" : "password";
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
});

