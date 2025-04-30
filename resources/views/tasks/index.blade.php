<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Todo App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .task-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .completed {
            text-decoration: line-through;
            color: #888;
        }
        .task-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .task-item:last-child {
            border-bottom: none;
        }
        .task-checkbox {
            width: 18px;
            height: 18px;
            margin-right: 12px;
        }
        .task-content {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .task-title {
            margin: 0 0 0 10px;
            font-size: 14px;
        }
        .task-meta {
            color: #999;
            font-size: 12px;
            margin-left: 8px;
        }
        .task-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-left: auto;
            margin-right: 10px;
        }
        .delete-btn {
            color: #ccc;
            background: none;
            border: none;
            padding: 4px 8px;
            cursor: pointer;
        }
        .delete-btn:hover {
            color: #dc3545;
        }
        .input-group {
            margin-bottom: 1rem;
        }
        .counter {
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-right: none;
            color: #666;
            padding: 0.375rem 0.75rem;
        }
        .add-btn {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        .add-btn:hover {
            background-color: #45a049;
            border-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="task-container">
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="taskFilter" id="showAllTasks" value="all" checked>
                    <label class="form-check-label" for="showAllTasks">
                        Show All Tasks
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="taskFilter" id="showCompletedTasks" value="completed">
                    <label class="form-check-label" for="showCompletedTasks">
                        Show Completed Tasks
                    </label>
                </div>
            </div>

            <div class="input-group">
                <span class="counter" id="taskCounter">0</span>
                <input type="text" id="taskInput" class="form-control" placeholder="Project # To Do" required>
                <button class="btn add-btn text-white" type="button" id="addTask">Add</button>
            </div>

            <div id="taskList">
                <!-- Tasks will be dynamically added here -->
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this task?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taskInput = document.getElementById('taskInput');
            const addTaskBtn = document.getElementById('addTask');
            const taskList = document.getElementById('taskList');
            const taskCounter = document.getElementById('taskCounter');
            const taskFilter = document.getElementsByName('taskFilter');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            let tasks = [];
            let taskToDelete = null;

            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Load tasks on page load
            loadTasks();

            // Add task button click
            addTaskBtn.addEventListener('click', addTask);
            taskInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addTask();
                }
            });

            async function addTask() {
                const title = taskInput.value.trim();
                if (!title) return;
                
                try {
                    const response = await fetch('/tasks', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ title })
                    });

                    if (!response.ok) {
                        const data = await response.json();
                        throw new Error(data.error || 'Failed to add task');
                    }

                    const task = await response.json();
                    tasks.unshift(task);
                    renderTasks();
                    taskInput.value = '';
                    updateCounter();
                } catch (error) {
                    alert(error.message);
                }
            }

            // Load all tasks
            async function loadTasks() {
                try {
                    const response = await fetch('/tasks');
                    tasks = await response.json();
                    renderTasks();
                    updateCounter();
                } catch (error) {
                    console.error('Error loading tasks:', error);
                }
            }

            function updateCounter() {
                taskCounter.textContent = tasks.length;
            }

            // Render tasks
            function renderTasks() {
                taskList.innerHTML = '';
                const selectedFilter = document.querySelector('input[name="taskFilter"]:checked').value;
                let filteredTasks = tasks;
                
                if (selectedFilter === 'completed') {
                    filteredTasks = tasks.filter(task => task.completed);
                } else if (selectedFilter === 'active') {
                    filteredTasks = tasks.filter(task => !task.completed);
                }
                
                filteredTasks.forEach(task => {
                    const taskElement = document.createElement('div');
                    taskElement.className = 'task-item';
                    
                    const taskContent = document.createElement('div');
                    taskContent.className = 'task-content';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'task-checkbox';
                    checkbox.checked = task.completed;
                    checkbox.addEventListener('change', () => toggleTask(task.id));
                    
                    const title = document.createElement('span');
                    title.className = 'task-title' + (task.completed ? ' completed' : '');
                    title.textContent = task.title;
                    
                    const meta = document.createElement('span');
                    meta.className = 'task-meta';
                    meta.textContent = 'a few seconds ago';
                    
                    const avatar = document.createElement('img');
                    avatar.className = 'task-avatar';
                    avatar.src = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                    avatar.alt = 'User avatar';
                    
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.addEventListener('click', () => showDeleteConfirmation(task.id));
                    
                    taskContent.appendChild(checkbox);
                    taskContent.appendChild(title);
                    taskContent.appendChild(meta);
                    
                    taskElement.appendChild(taskContent);
                    taskElement.appendChild(avatar);
                    taskElement.appendChild(deleteBtn);
                    
                    taskList.appendChild(taskElement);
                });
            }

            // Toggle task completion
            async function toggleTask(taskId) {
                try {
                    const response = await fetch(`/tasks/${taskId}/toggle`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    
                    if (!response.ok) throw new Error('Failed to toggle task');
                    
                    const updatedTask = await response.json();
                    tasks = tasks.map(task => 
                        task.id === taskId ? updatedTask : task
                    );
                    renderTasks();
                } catch (error) {
                    console.error('Error toggling task:', error);
                }
            }

            
            function showDeleteConfirmation(id) {
                taskToDelete = id;
                deleteModal.show();
            }

            
            async function deleteTask(taskId) {
                try {
                    const response = await fetch(`/tasks/${taskId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    
                    if (!response.ok) throw new Error('Failed to delete task');
                    
                    tasks = tasks.filter(task => task.id !== taskId);
                    renderTasks();
                    updateCounter();
                } catch (error) {
                    console.error('Error deleting task:', error);
                }
            }

            
            taskFilter.forEach(radio => {
                radio.addEventListener('change', renderTasks);
            });

            
            document.getElementById('confirmDelete').addEventListener('click', () => {
                if (taskToDelete) {
                    deleteTask(taskToDelete);
                    deleteModal.hide();
                    taskToDelete = null;
                }
            });
        });
    </script>
</body>
</html>
