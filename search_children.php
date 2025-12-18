
<?php
include __DIR__ . '/php/db_connect.php'; // safer absolute path
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Children</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
body { min-height: 100vh; background: #eef6f8; display: flex; justify-content: center; align-items: flex-start; padding: 40px; }
.container { background: #fff; width: 500px; padding: 35px; border-radius: 18px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
h2 { text-align: center; margin-bottom: 25px; color: #2c3e50; }
input { width: 100%; padding: 12px; margin-bottom: 15px; border: 2px solid #dcdde1; border-radius: 8px; font-size: 15px; }
input:focus { outline: none; border-color: #f4b942; box-shadow: 0 0 5px rgba(244,185,66,0.5); }
button { padding: 10px 16px; margin-top: 10px; background: #f4b942; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; }
button:hover { background: #e0a82e; }
#results { margin-top: 20px; }
.child-card { padding: 12px; margin-bottom: 12px; background: #f7f9fb; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
.child-info { display: flex; flex-direction: column; }
.child-info span { font-size: 14px; color: #34495e; }
.actions button { margin-left: 5px; }
</style>
</head>
<body>

<div class="container">
    <h2>Search Children</h2>
    <input type="text" id="search_input" placeholder="Type child name or email">

    <div id="results"></div>

    <button onclick="window.location.href='dashboard.php'">Go Back to Dashboard</button>
</div>

<script>
const input = document.getElementById('search_input');
const resultsDiv = document.getElementById('results');

input.addEventListener('input', function() {
    const query = this.value.trim();

    if(query.length < 1) {
        resultsDiv.innerHTML = '';
        return;
    }

    fetch(`search_children_ajax.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            if(data.length === 0) {
                resultsDiv.innerHTML = '<p>No children found.</p>';
                return;
            }

            data.forEach(child => {
                const card = document.createElement('div');
                card.className = 'child-card';

                const info = document.createElement('div');
                info.className = 'child-info';
                info.innerHTML = `<strong>${child.name}</strong><span>${child.email}</span>`;

                const actions = document.createElement('div');
                actions.className = 'actions';

                // Add Device button
                const addBtn = document.createElement('button');
                addBtn.textContent = 'Add Device';
                addBtn.onclick = () => {
                    window.location.href = `devices.php?assigned_to=${child.id}`;
                };

                actions.appendChild(addBtn);

                card.appendChild(info);
                card.appendChild(actions);

                resultsDiv.appendChild(card);
            });
        });
});
</script>

</body>
</html>
