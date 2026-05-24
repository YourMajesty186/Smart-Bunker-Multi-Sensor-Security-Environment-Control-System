<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMMAND CENTER: BUNKER ALPHA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=VT323&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #121212;
            --panel-bg: #1a1c18;
            --army-green: #4b5320;
            --bright-green: #33ff00;
            --radar-green: #4caf50;
            --hazard-yellow: #f59e0b;
            --alert-red: #ef4444;
            --steel: #475569;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
                repeating-linear-gradient(45deg, #1a1a1a 0, #1a1a1a 10px, #222 10px, #222 20px);
            color: var(--bright-green);
            font-family: 'VT323', monospace;
            margin: 0; padding: 20px;
            text-shadow: 0 0 5px rgba(51, 255, 0, 0.5);
            min-height: 100vh;
        }

        h1 {
            text-align: center;
            font-family: 'Black Ops One', cursive;
            font-size: 2.5rem;
            color: #e2e8f0;
            letter-spacing: 4px;
            margin-bottom: 40px;
            text-transform: uppercase;
            border-bottom: 4px solid var(--army-green);
            display: inline-block; width: 100%; position: relative;
        }

        h1::after {
            content: "TOP SECRET // AUTHORIZED PERSONNEL ONLY";
            display: block; font-size: 0.4em; font-family: 'VT323', monospace;
            color: var(--hazard-yellow); letter-spacing: 2px; margin-top: 5px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 30px; position: relative; z-index: 3;
        }

        .card {
            background: var(--panel-bg);
            border: 2px solid var(--army-green);
            padding: 0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
            position: relative;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%);
        }

        .header {
            background: #2a2f25; padding: 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--army-green);
            background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.2) 10px, rgba(0,0,0,0.2) 20px);
        }

        .header h2 { margin: 0; color: #fff; font-family: 'Black Ops One', cursive; font-size: 1.4rem; }

        .content-body { padding: 20px; }

        .mode-badge {
            font-family: 'Black Ops One', cursive; font-size: 0.9em;
            background: #000; color: var(--hazard-yellow);
            padding: 5px 12px; border: 1px solid var(--hazard-yellow); border-radius: 2px;
        }

        .data-row {
            display: flex; justify-content: space-between; margin: 15px 0;
            font-size: 1.4em; border-bottom: 1px dashed #333; padding-bottom: 5px;
        }
        .data-row span:first-child { color: #8899a6; text-transform: uppercase; }
        .val { font-weight: bold; color: var(--bright-green); }

        /* AREA GRAFIK */
        .chart-wrapper {
            background: #000;
            border: 1px solid var(--army-green);
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
            position: relative;
        }
        .chart-wrapper::before {
            content: "ACOUSTIC WAVEFORM MONITOR";
            position: absolute; top: -10px; left: 10px;
            background: var(--panel-bg); color: var(--radar-green);
            font-size: 0.8em; padding: 0 5px;
        }
        .chart-container { height: 180px; width: 100%; }

        /* CONTROLS */
        .controls {
            margin-top: 25px; background: #111; padding: 15px;
            border: 1px solid #333; position: relative;
        }
        .controls::before {
            content: "MANUAL OVERRIDE SYSTEM";
            position: absolute; top: -10px; left: 10px;
            background: var(--panel-bg); padding: 0 5px;
            font-size: 0.8em; color: #666;
        }

        .control-group { display: flex; justify-content: space-between; align-items: center; margin: 15px 0; }

        .btn {
            padding: 8px 18px; border: 2px solid transparent; cursor: pointer;
            font-family: 'Black Ops One', cursive; font-size: 1em; transition: 0.2s;
            text-transform: uppercase; margin-left: 5px;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }
        .btn:hover { transform: translateY(-2px); }

        .btn-auto { background: #333; color: #fff; border-color: #555; }
        .btn-auto.active { background: var(--radar-green); color: #000; box-shadow: 0 0 10px var(--radar-green); border-color: var(--radar-green); }

        .btn-man { background: #333; color: #fff; border-color: #555; }
        .btn-man.active { background: var(--hazard-yellow); color: #000; box-shadow: 0 0 10px var(--hazard-yellow); border-color: var(--hazard-yellow); }

        .btn-on { background: #1a1a1a; color: #555; border: 1px solid #333; }
        .btn-on.active { background: var(--radar-green); color: black; box-shadow: 0 0 8px var(--radar-green); border-color: var(--radar-green); }

        .btn-off { background: #1a1a1a; color: #555; border: 1px solid #333; }
        .btn-off.active { background: var(--alert-red); color: white; box-shadow: 0 0 8px var(--alert-red); border-color: var(--alert-red); }

        .disabled { pointer-events: none; opacity: 0.3; filter: grayscale(100%); }

        /* Specific Colors */
        #pintu { color: var(--hazard-yellow); }
        #gempa { color: var(--alert-red); }
        #suhu, #lembab { color: #00e5ff; }

    </style>
</head>
<body>

    <h1>BUNKER COMMAND SYSTEM</h1>

    <div class="grid">
        <div class="card">
            <div class="header">
                <h2>🛡️ SECTOR A: SECURITY</h2>
                <div id="esp1-mode-display" class="mode-badge">AUTO</div>
            </div>
            
            <div class="content-body">
                <div class="data-row"><span>DOOR STATUS</span> <span id="pintu" class="val">LOCKING...</span></div>
                <div class="data-row"><span>SEISMIC ACTIVITY</span> <span id="gempa" class="val">ANALYZING...</span></div>
                
                <div class="chart-wrapper">
                    <div class="chart-container">
                        <canvas id="grafikSuara"></canvas>
                    </div>
                </div>

                <div class="controls">
                    <div class="control-group">
                        <span>PROTOCOL:</span>
                        <div>
                            <button class="btn btn-auto" onclick="setControl('esp1_mode','AUTO')" id="btn-esp1-auto">AUTO</button>
                            <button class="btn btn-man" onclick="setControl('esp1_mode','MANUAL')" id="btn-esp1-man">MANUAL</button>
                        </div>
                    </div>

                    <div class="control-group" id="ctrl-pintu">
                        <span>BLAST DOOR:</span>
                        <div>
                            <button class="btn btn-on" onclick="setControl('esp1_servo','OPEN')" id="btn-esp1-open">OPEN</button>
                            <button class="btn btn-off" onclick="setControl('esp1_servo','CLOSE')" id="btn-esp1-close">CLOSE</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="header">
                <h2>🌡️ SECTOR B: LIFE SUPPORT</h2>
                <div id="esp2-mode-display" class="mode-badge">AUTO</div>
            </div>

            <div class="content-body">
                <div class="data-row"><span>TEMPERATURE</span> <span id="suhu" class="val">--.- °C</span></div>
                <div class="data-row"><span>HUMIDITY</span> <span id="lembab" class="val">-- %</span></div>
                <div class="data-row"><span>VENTILATION</span> <span id="status_kipas" class="val">--</span></div>
                <div class="data-row"><span>LIGHTING</span> <span id="status_cahaya" class="val">--</span></div>

                <div class="controls">
                    <div class="control-group">
                        <span>PROTOCOL:</span>
                        <div>
                            <button class="btn btn-auto" onclick="setControl('esp2_mode','AUTO')" id="btn-esp2-auto">AUTO</button>
                            <button class="btn btn-man" onclick="setControl('esp2_mode','MANUAL')" id="btn-esp2-man">MANUAL</button>
                        </div>
                    </div>

                    <div class="control-group" id="ctrl-kipas">
                        <span>TURBINE:</span>
                        <div>
                            <button class="btn btn-on" onclick="setControl('esp2_kipas','ON')" id="btn-esp2-kon">ENGAGE</button>
                            <button class="btn btn-off" onclick="setControl('esp2_kipas','OFF')" id="btn-esp2-koff">HALT</button>
                        </div>
                    </div>

                    <div class="control-group" id="ctrl-led">
                        <span>NIGHT LIGHT:</span>
                        <div>
                            <button class="btn btn-on" onclick="setControl('esp2_led','ON')" id="btn-esp2-lon">ON</button>
                            <button class="btn btn-off" onclick="setControl('esp2_led','OFF')" id="btn-esp2-loff">OFF</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- 1. SETUP GRAFIK ---
        const ctx = document.getElementById('grafikSuara').getContext('2d');
        const soundChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], 
                datasets: [{
                    label: 'dB Level',
                    data: [], 
                    borderColor: '#33ff00',
                    backgroundColor: 'rgba(51, 255, 0, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { display: false },
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#222' },
                        ticks: { color: '#33ff00', font: { family: 'VT323' } } 
                    }
                },
                plugins: { legend: { display: false } }
            }
        });

        // --- 2. UPDATE DATA ---
        setInterval(refreshData, 1000); 

        function refreshData() {
            // Ambil Data Sensor
            fetch('api.php?read_sensors=1')
                .then(res => res.json())
                .then(data => {
                    if(data.esp1) {
                        document.getElementById('pintu').innerText = data.esp1.pintu;
                        document.getElementById('gempa').innerText = data.esp1.gempa;
                        
                        // Update Grafik Suara
                        updateChart(soundChart, data.esp1.suara);
                    }
                    if(data.esp2) {
                        document.getElementById('suhu').innerText = data.esp2.suhu + " °C";
                        document.getElementById('lembab').innerText = data.esp2.kelembaban + " %";
                        document.getElementById('status_kipas').innerText = data.esp2.kipas;
                        document.getElementById('status_cahaya').innerText = data.esp2.cahaya;
                    }
                })
                .catch(err => console.log(err));

            // Ambil Status Tombol
            fetch('api.php?read_controls=1')
                .then(res => res.json())
                .then(ctrl => {
                    updateButtons(ctrl);
                })
                .catch(err => console.log(err));
        }

        // Fungsi Update Grafik
        function updateChart(chart, value) {
            const timeNow = new Date().toLocaleTimeString();
            chart.data.labels.push(timeNow);
            chart.data.datasets[0].data.push(value);

            if (chart.data.labels.length > 20) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }
            chart.update();
        }

        function updateButtons(ctrl) {
            const setActive = (id, isActive) => {
                const btn = document.getElementById(id);
                if(btn) btn.classList.toggle('active', isActive);
            };

            // ESP 1 Buttons
            setActive('btn-esp1-auto', ctrl.esp1_mode === 'AUTO');
            setActive('btn-esp1-man', ctrl.esp1_mode === 'MANUAL');
            setActive('btn-esp1-open', ctrl.esp1_servo === 'OPEN');
            setActive('btn-esp1-close', ctrl.esp1_servo === 'CLOSE');
            
            const ctrlPintu = document.getElementById('ctrl-pintu');
            if(ctrlPintu) ctrlPintu.classList.toggle('disabled', ctrl.esp1_mode === 'AUTO');
            
            const modeDisplay1 = document.getElementById('esp1-mode-display');
            if(modeDisplay1) modeDisplay1.innerText = ctrl.esp1_mode;

            // ESP 2 Buttons
            setActive('btn-esp2-auto', ctrl.esp2_mode === 'AUTO');
            setActive('btn-esp2-man', ctrl.esp2_mode === 'MANUAL');
            setActive('btn-esp2-kon', ctrl.esp2_kipas === 'ON');
            setActive('btn-esp2-koff', ctrl.esp2_kipas === 'OFF');
            setActive('btn-esp2-lon', ctrl.esp2_led === 'ON');
            setActive('btn-esp2-loff', ctrl.esp2_led === 'OFF');

            const ctrlKipas = document.getElementById('ctrl-kipas');
            const ctrlLed = document.getElementById('ctrl-led');
            
            if(ctrlKipas) ctrlKipas.classList.toggle('disabled', ctrl.esp2_mode === 'AUTO');
            if(ctrlLed) ctrlLed.classList.toggle('disabled', ctrl.esp2_mode === 'AUTO');
            
            const modeDisplay2 = document.getElementById('esp2-mode-display');
            if(modeDisplay2) modeDisplay2.innerText = ctrl.esp2_mode;
        }

        function setControl(col, val) {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('column', col);
            formData.append('value', val);
            fetch('api.php', { method: 'POST', body: formData }).then(refreshData);
        }
        
        refreshData(); 
    </script>
</body>
</html>