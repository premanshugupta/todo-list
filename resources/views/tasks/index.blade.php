<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Todo List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Quick fix for the background */
        body { background: #f8f9fa }
        
        .todo-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin: 2rem auto;
            max-width: 800px;
        }

        /* TODO: maybe change these colors later */
        .done { 
            text-decoration: line-through;
            color: #888;
        }

        .todo-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .todo-item:last-child { border: none }

        /* Checkbox styling */
        .checkbox {
            width: 18px;
            height: 18px;
            margin-right: 12px;
        }

        .item-text {
            font-size: 14px;
            margin-left: 10px;
            flex: 1;
        }

        .timestamp {
            color: #999;
            font-size: 12px;
            margin-left: 8px;
        }

        /* User pic */
        .user-pic {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin: 0 10px;
        }

        .del-btn {
            opacity: 0.5;
            background: none;
            border: none;
            cursor: pointer;
        }
        .del-btn:hover { opacity: 1 }

        #task_num {
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-right: none;
            padding: 6px 12px;
        }

        .add-btn {
            background: #4CAF50 !important;
            border: none;
        }
    </style>
</head>
<body>
    <div class="todo-container">
        <!-- filters -->
        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input type="radio" class="form-check-input" name="filter" id="all" value="all" checked>
                <label class="form-check-label" for="all">Show All Tasks</label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" class="form-check-input" name="filter" id="done" value="completed">
                <label class="form-check-label" for="done">Show Completed</label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" class="form-check-input" name="filter" id="pending" value="pending">
                <label class="form-check-label" for="pending">Show Non Completed</label>
            </div>
        </div>

        <!-- add new todo -->
        <div class="input-group mb-3">
            <span id="task_num">0</span>
            <input type="text" id="new_todo" class="form-control" placeholder="Project # To Do">
            <button class="btn add-btn text-white" id="add">Add</button>
        </div>

        <!-- todo list -->
        <div id="todos"></div>
    </div>

    <!-- delete popup -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Task?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this task?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // grab DOM elements
        const newTodo = document.getElementById('new_todo')
        const todoList = document.getElementById('todos')
        const counter = document.getElementById('task_num')
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'))
        
        let todos = [] // store todos here
        let todoToDelete = null // for delete confirmation

        // get csrf token
        const csrf = document.querySelector('meta[name="csrf-token"]').content

        // load todos when page loads
        getTodos()

        // add new todo
        document.getElementById('add').onclick = addTodo
        newTodo.onkeyup = e => {
            if(e.key === 'Enter') addTodo()
        }

        async function addTodo() {
            let txt = newTodo.value.trim()
            if(!txt) return // don't add empty todos
            
            try {
                let res = await fetch('/tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({title: txt})
                })

                if(!res.ok) {
                    let data = await res.json()
                    throw new Error(data.error || 'Failed to add')
                }

                let todo = await res.json()
                todos.unshift(todo)
                showTodos()
                newTodo.value = ''
                updateCount()
            } catch(err) {
                alert(err.message) // quick error handling
            }
        }

        // get all todos from server
        async function getTodos() {
            try {
                let res = await fetch('/tasks')
                todos = await res.json()
                showTodos()
                updateCount()
            } catch(err) {
                console.error('Failed to load todos:', err)
            }
        }

        // show todos in the list
        function showTodos() {
            todoList.innerHTML = ''
            let filter = document.querySelector('input[name="filter"]:checked').value
            
            // filter todos
            let filtered = todos
            if(filter === 'completed') {
                filtered = todos.filter(t => t.completed)
            } else if(filter === 'pending') {
                filtered = todos.filter(t => !t.completed)
            }
            
            filtered.forEach(todo => {
                let div = document.createElement('div')
                div.className = 'todo-item'
                
                let check = document.createElement('input')
                check.type = 'checkbox'
                check.className = 'checkbox'
                check.checked = todo.completed
                check.onclick = () => toggleTodo(todo.id)
                
                let text = document.createElement('span')
                text.className = 'item-text' + (todo.completed ? ' done' : '')
                text.textContent = todo.title
                
                let time = document.createElement('span')
                time.className = 'timestamp'
                time.textContent = 'a few seconds ago'
                
                let pic = document.createElement('img')
                pic.className = 'user-pic'
                pic.src = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'
                
                let del = document.createElement('button')
                del.className = 'del-btn'
                del.innerHTML = '<i class="fas fa-trash"></i>'
                del.onclick = () => confirmDelete(todo.id)
                
                div.append(check, text, time, pic, del)
                todoList.appendChild(div)
            })
        }

        // update the counter
        function updateCount() {
            counter.textContent = todos.length
        }

        // toggle todo completion
        async function toggleTodo(id) {
            try {
                let res = await fetch(`/tasks/${id}/toggle`, {
                    method: 'PUT',
                    headers: {'X-CSRF-TOKEN': csrf}
                })
                
                if(!res.ok) throw new Error('Failed to update')
                
                let updated = await res.json()
                todos = todos.map(t => t.id === id ? updated : t)
                showTodos()
            } catch(err) {
                console.error('Toggle failed:', err)
            }
        }

        // show delete confirmation
        function confirmDelete(id) {
            todoToDelete = id
            deleteModal.show()
        }

        // delete todo
        async function deleteTodo(id) {
            try {
                let res = await fetch(`/tasks/${id}`, {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': csrf}
                })
                
                if(!res.ok) throw new Error('Delete failed')
                
                todos = todos.filter(t => t.id !== id)
                showTodos()
                updateCount()
            } catch(err) {
                console.error('Delete failed:', err)
            }
        }

        // filter change
        document.querySelectorAll('input[name="filter"]').forEach(radio => {
            radio.onchange = showTodos
        })

        // delete confirmation
        document.getElementById('delete').onclick = () => {
            if(todoToDelete) {
                deleteTodo(todoToDelete)
                deleteModal.hide()
                todoToDelete = null
            }
        }
    </script>
</body>
</html>
