document.addEventListener('DOMContentLoaded', async () => {
    // Basic config
    const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
    const startHour = 8;
    const endHour = 22;
    let selectedTool = 'unavailable'; // default tool
    let email = '';
    
    const generateTimeSlots = () => {
        const slots = [];
        for (let h = startHour; h < endHour; h++) {
            slots.push(`${h.toString().padStart(2, '0')}:00 - ${(h+1).toString().padStart(2,'0')}:00`);
        }
        return slots;
    };
    const timeSlots = generateTimeSlots();
    
    // UI Elements
    const tableHead = document.querySelector('#schedule-table thead');
    const tableBody = document.querySelector('#schedule-table tbody');
    const loggedEmailDisplay = document.getElementById('logged-email');
    const nameInput = document.getElementById('name');
    const disclaimerModal = document.getElementById('disclaimer-modal');
    const btnSaveFinal = document.getElementById('btn-save-final');
    const btnAcceptDisclaimer = document.getElementById('disclaimer-accept');
    const btnCancelDisclaimer = document.getElementById('disclaimer-cancel');

    // Grid state
    let currentGrid = Array.from({length: 14}, () => 
        Array.from({length: 5}, () => ({ state: 'neutral', comment: '' }))
    );

    // Initial check of auth and load existing data
    try {
        const authRes = await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'check_auth' }) });
        const authData = await authRes.json();
        if(!authData.success) {
            alert("No has iniciado sesión correctamente.");
            window.location.href = 'index.html';
            return;
        }
        email = authData.email;
        loggedEmailDisplay.textContent = email;

        // Load existing schedule if exists
        const dataRes = await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'get_my_schedule' }) });
        const existingData = await dataRes.json();
        if(existingData.success && existingData.data) {
            const entry = existingData.data;
            nameInput.value = entry.name || '';
            if(entry.grid && entry.grid.length >= 13) {
                // Map old 13 or new 14 rows
                for(let r=0; r < entry.grid.length && r < 14; r++) {
                    currentGrid[r] = entry.grid[r];
                }
            }
        }

        // Check if is admin to show dashboard
        const adminRes = await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'check_admin' }) });
        const adminData = await adminRes.json();
        if(adminData.success) {
            document.getElementById('admin-dashboard-section').classList.remove('hidden');
            // Hide the registration section for the admin view
            document.querySelector('.registration-section').classList.add('hidden');
            setupAdminLogic();
        }
    } catch(e) {
        console.error("Error loading data", e);
    }

    function setupAdminLogic() {
        const btnCalc = document.getElementById('btn-calculate');
        const resultsContainer = document.getElementById('results-container');

        btnCalc.onclick = async () => {
            btnCalc.disabled = true;
            btnCalc.textContent = "Calculando...";
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'get_all_schedules' }) });
                const result = await res.json();
                if(!result.success) return alert(result.message);

                const participantsData = result.data;
                const comboType = document.querySelector('input[name="combo-type"]:checked').value;
                const numRows = 14; // 8:00 to 22:00
                const numCols = 5;  // Mon-Fri

                // A "block" = { col (day), startRow, duration }
                const blocksOf = (duration) => {
                    const result = [];
                    for (let c = 0; c < numCols; c++) {
                        for (let r = 0; r <= numRows - duration; r++) {
                            result.push({ col: c, startRow: r, duration });
                        }
                    }
                    return result;
                };

                const all1h = blocksOf(1);
                const all2h = blocksOf(2);
                const all3h = blocksOf(3);

                const combos = [];
                if (comboType === '2_2') {
                    for (let i = 0; i < all2h.length; i++) {
                        for (let j = i + 1; j < all2h.length; j++) {
                            if (all2h[i].col !== all2h[j].col) combos.push([all2h[i], all2h[j]]);
                        }
                    }
                } else if (comboType === '2_1_1') {
                    for (const b2 of all2h) {
                        for (const b1a of all1h) {
                            if (b1a.col === b2.col) continue;
                            for (const b1b of all1h) {
                                if (b1b.col === b2.col || b1b.col === b1a.col) continue;
                                if (b1a.col > b1b.col) continue;
                                combos.push([b2, b1a, b1b]);
                            }
                        }
                    }
                } else if (comboType === '3_1') {
                    for (const b3 of all3h) {
                        for (const b1 of all1h) {
                            if (b3.col !== b1.col) combos.push([b3, b1]);
                        }
                    }
                }

                const canAttendBlock = (person, block) => {
                    for (let offset = 0; offset < block.duration; offset++) {
                        const r = block.startRow + offset;
                        const c = block.col;
                        if (person.grid[r] && person.grid[r][c].state === 'unavailable') return false;
                    }
                    return true;
                };

                const ranked = [];
                combos.forEach(blocks => {
                    let missingStudents = new Set();
                    let isProfMissing = false;
                    participantsData.forEach(p => {
                        let grid = p.grid;
                        if (grid.length < 14) {
                            grid = Array.from({length: 14}, (_, i) => 
                                p.grid[i] || Array.from({length: 5}, () => ({ state: 'neutral', comment: '' }))
                            );
                        }
                        const personWithNormalGrid = { ...p, grid };
                        let canAttend = blocks.every(block => canAttendBlock(personWithNormalGrid, block));
                        if (!canAttend) {
                            if (p.role === 'profesor') isProfMissing = true;
                            else missingStudents.add(p.name);
                        }
                    });
                    if (!isProfMissing) ranked.push({ blocks, missingCount: missingStudents.size, missingUsers: Array.from(missingStudents) });
                });

                ranked.sort((a, b) => a.missingCount - b.missingCount);
                renderResults(ranked, participantsData);
            } catch(e) {
                console.error(e);
                alert("Error en el cálculo.");
            }
            btnCalc.disabled = false;
            btnCalc.textContent = "Buscar Mejores Combinaciones";
        };

        const btnClear = document.getElementById('btn-clear-data');
        if (btnClear) {
            btnClear.onclick = async () => {
                if (!confirm("⚠️ ¿Estás seguro de que deseas BORRAR TODOS los datos? Esta acción no se puede deshacer.")) return;
                try {
                    const res = await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'clear_all_schedules' }) });
                    const result = await res.json();
                    if (result.success) {
                        alert('✅ Datos eliminados.');
                        window.location.reload();
                    } else alert(result.message);
                } catch(e) { alert('Error de conexión.'); }
            }
        }
    }

    function renderResults(ranked, participantsData) {
        const resultsContainer = document.getElementById('results-container');
        resultsContainer.classList.remove('hidden');
        resultsContainer.innerHTML = '<h3>🌟 Mejores Opciones Encontradas</h3>';

        const top = ranked.slice(0, 10);
        if(top.length === 0) {
            resultsContainer.innerHTML += '<p>No se encontraron combinaciones viables (el profesor siempre tiene choque).</p>';
            return;
        }

        top.forEach((item, idx) => {
            const card = document.createElement('div');
            card.className = 'combo-card';
            card.style.marginBottom = '1rem';
            card.style.display = 'flex';
            card.style.alignItems = 'center';
            card.style.gap = '1.5rem';
            
            const timeStr = item.blocks.map(block => {
                const dayName = days[block.col];
                const startH = timeSlots[block.startRow].split(' ')[0];
                const endH = timeSlots[block.startRow + block.duration - 1].split(' - ')[1];
                return `<span class="stat-pill pill-blue">${dayName} ${startH}–${endH}</span>`;
            }).join(' + ');
            
            let conflictsHtml = '';
            const blocksParam = encodeURIComponent(JSON.stringify(item.blocks));
            if (item.missingCount === 0) {
                conflictsHtml = '<span style="color:var(--success)">✅ ¡Cero Choques!</span>';
            } else {
                const nameLinks = item.missingUsers.map(m => `<span class="clickable-person" style="text-decoration:underline; cursor:pointer;" data-name="${m}" data-proposal="${blocksParam}">${m}</span>`).join(', ');
                conflictsHtml = `⚠️ ${item.missingCount} Choque(s): ${nameLinks}`;
            }

            const activeCells = new Set();
            item.blocks.forEach(block => {
                for (let offset = 0; offset < block.duration; offset++) {
                    activeCells.add(`${block.startRow + offset}_${block.col}`);
                }
            });

            let miniGridHtml = `<table class="mini-schedule"><thead><tr><th>H</th>`;
            days.forEach(d => miniGridHtml += `<th>${d[0]}</th>`);
            miniGridHtml += `</tr></thead><tbody>`;
            
            for (let r = 0; r < 14; r++) {
                const rowLabel = (startHour + r).toString().padStart(2, '0');
                miniGridHtml += `<tr><td style="font-size:0.5rem; opacity:0.5; padding:0 2px;">${rowLabel}</td>`;
                for (let c = 0; c < 5; c++) {
                    const isActive = activeCells.has(`${r}_${c}`);
                    miniGridHtml += `<td><div class="mini-cell ${isActive ? 'mini-active' : 'mini-inactive'}"></div></td>`;
                }
                miniGridHtml += `</tr>`;
            }
            miniGridHtml += `</tbody></table>`;

            card.innerHTML = `
                ${miniGridHtml}
                <div class="combo-info">
                    <h4>Opción #${idx+1}</h4>
                    <div style="margin: 0.5rem 0;">${timeStr}</div>
                    <div style="font-size: 0.9rem;">${conflictsHtml}</div>
                </div>
            `;
            resultsContainer.appendChild(card);
        });

        resultsContainer.querySelectorAll('.clickable-person').forEach(el => {
            el.onclick = () => {
                const name = encodeURIComponent(el.dataset.name);
                const proposal = el.dataset.proposal || '';
                window.open(`visor.html?name=${name}&proposal=${proposal}`, '_blank', 'width=800,height=700');
            };
        });
    }

    // Table Init
    let theadHTML = `<tr><th class="header-time">Hora</th>`;
    days.forEach(d => theadHTML += `<th>${d}</th>`);
    theadHTML += `</tr>`;
    tableHead.innerHTML = theadHTML;

    let isDragging = false;
    let pendingCells = [];

    const renderGrid = () => {
        tableBody.innerHTML = '';
        timeSlots.forEach((slotInfo, rIndex) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="header-time">${slotInfo}</td>`;
            days.forEach((d, cIndex) => {
                const td = document.createElement('td');
                const cellHTML = document.createElement('div');
                cellHTML.className = `cell ${currentGrid[rIndex][cIndex].state}`;
                cellHTML.addEventListener('mousedown', (e) => handleCellClick(e, rIndex, cIndex));
                cellHTML.addEventListener('mouseenter', (e) => { if(isDragging) handleCellClick(e, rIndex, cIndex, true); });
                td.appendChild(cellHTML);
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    };

    const handleCellClick = (e, row, col, fromDrag = false) => {
        if(!fromDrag) isDragging = true;
        if (selectedTool === 'available') {
            currentGrid[row][col] = { state: 'available', comment: '' };
        } else if (selectedTool === 'clear') {
            currentGrid[row][col] = { state: 'neutral', comment: '' };
        } else if (selectedTool === 'unavailable') {
            currentGrid[row][col] = { state: 'unavailable', comment: 'Ocupado' };
        }
        renderGrid();
    };

    document.addEventListener('mouseup', () => isDragging = false);

    const btnToolUnavail = document.getElementById('tool-unavailable');
    const btnToolAvail = document.getElementById('tool-available');
    const btnToolClear = document.getElementById('tool-clear');
    const btnToolMagic = document.getElementById('tool-magic');

    const updateTools = (tool) => {
        selectedTool = tool;
        btnToolUnavail.classList.toggle('active-tool-red', tool === 'unavailable');
        btnToolAvail.classList.toggle('active-tool-green', tool === 'available');
    };
    btnToolUnavail.onclick = () => updateTools('unavailable');
    btnToolAvail.onclick = () => updateTools('available');
    btnToolClear.onclick = () => updateTools('clear');
    btnToolMagic.onclick = () => {
        currentGrid.forEach(row => row.forEach(col => { if(col.state === 'neutral') col.state = 'available'; }));
        renderGrid();
    };

    btnSaveFinal.onclick = () => {
        if(!nameInput.value.trim()) return alert("Ingresa tu nombre.");
        disclaimerModal.classList.remove('hidden');
    };
    btnCancelDisclaimer.onclick = () => disclaimerModal.classList.add('hidden');
    btnAcceptDisclaimer.onclick = async () => {
        btnAcceptDisclaimer.disabled = true;
        try {
            const res = await fetch('api.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'save_schedule', name: nameInput.value.trim(), grid: currentGrid })
            });
            const result = await res.json();
            if(result.success) {
                alert("✅ Guardado con éxito.");
                window.location.reload();
            } else alert(result.message);
        } catch(e) { alert("Error al guardar."); }
        btnAcceptDisclaimer.disabled = false;
    };

    const logoutButtons = [document.getElementById('btn-logout'), document.getElementById('btn-admin-logout')];
    logoutButtons.forEach(btn => {
        if (btn) {
            btn.onclick = async () => {
                await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }) });
                window.location.href = 'index.html';
            };
        }
    });

    renderGrid();
});
