import sys
import numpy as np
import matplotlib
matplotlib.use('Agg') # WAJIB ADA DI ATAS
import matplotlib.pyplot as plt
import io
import base64

# --- FUNGSI-FUNGSI FUZZY ---
def trapezoid(x, a, b, c, d):
    val1 = 1.0
    if b - a > 0: val1 = (x - a) / (b - a)
    elif x < a: return 0
    val2 = 1.0
    if d - c > 0: val2 = (d - x) / (d - c)
    elif x > d: return 0
    return max(0, min(val1, 1, val2))

def triangle(x, a, b, c):
    if x <= a or x >= c: return 0
    if b - a == 0 or c - b == 0: return 0
    return max(0, min((x - a) / (b - a), (c - x) / (c - b)))

def fuzzify_delay(val):
    return {'Rendah': trapezoid(val,-1,0,20,40), 'Sedang': triangle(val,30,60,90), 'Tinggi': trapezoid(val,70,100,999,9999)}

def fuzzify_jitter(val):
    return {'Rendah': trapezoid(val,-1,0,3,7), 'Sedang': triangle(val,5,12.5,20), 'Tinggi': trapezoid(val,15,25,999,9999)}

def fuzzify_loss(val):
    return {'Rendah': trapezoid(val,-1,0,0.5,1.5), 'Sedang': triangle(val,1,3,5), 'Tinggi': trapezoid(val,4,6,99,101)}

def apply_rules(delay, jitter, loss):
    qos = {'Buruk': 0, 'Cukup': 0, 'Baik': 0, 'Sangat Baik': 0}
    qos['Buruk'] = max(qos['Buruk'], jitter['Tinggi'])
    qos['Buruk'] = max(qos['Buruk'], max(delay['Tinggi'], loss['Tinggi']))
    qos['Buruk'] = max(qos['Buruk'], min(delay['Sedang'], jitter['Tinggi']))
    qos['Sangat Baik'] = max(qos['Sangat Baik'], min(delay['Rendah'], jitter['Rendah'], loss['Rendah']))
    return qos


# --- FUNGSI UTAMA UNTUK GENERATE PLOT ---

def generate_plot(delay_val, jitter_val, loss_val):
    # === INI BAGIAN YANG DIPERBAIKI ===
    sets = {
        'delay': {
            'Rendah': (-1, 0, 20, 40), 
            'Sedang': (30, 60, 90), 
            'Tinggi': (70, 100, 999, 9999)
        },
        'jitter': {
            'Rendah': (-1, 0, 3, 7), 
            'Sedang': (5, 12.5, 20), 
            'Tinggi': (15, 25, 999, 9999)
        },
        'loss': {
            'Rendah': (-1, 0, 0.5, 1.5), 
            'Sedang': (1, 3, 5), 
            'Tinggi': (4, 6, 99, 101)
        },
        'output': {
            'Buruk': (0, 0, 20, 40), 
            'Cukup': (30, 45, 60), 
            'Baik':(50, 65, 80), 
            'Sangat Baik':(70, 85, 100, 100)
        }
    }
    # =================================

    f_delay = fuzzify_delay(delay_val)
    f_jitter = fuzzify_jitter(jitter_val)
    f_loss = fuzzify_loss(loss_val)
    inferred_qos = apply_rules(f_delay, f_jitter, f_loss)
    
    fig, (ax1, ax2, ax3, ax4) = plt.subplots(4, 1, figsize=(10, 12))
    
    # Plot 1: Delay
    x_delay = np.linspace(0, 100, 500)
    ax1.plot(x_delay, [trapezoid(x, *sets['delay']['Rendah']) for x in x_delay], 'r', label='Rendah')
    ax1.plot(x_delay, [triangle(x, *sets['delay']['Sedang']) for x in x_delay], 'g', label='Sedang')
    ax1.plot(x_delay, [trapezoid(x, *sets['delay']['Tinggi']) for x in x_delay], 'b', label='Tinggi')
    ax1.axvline(x=delay_val, color='k', linestyle='--', label=f'Input Delay = {delay_val}')
    ax1.set_title('Fungsi Keanggotaan Delay (ms)')
    ax1.legend()

    # Plot 2: Jitter
    x_jitter = np.linspace(0, 30, 500)
    ax2.plot(x_jitter, [trapezoid(x, *sets['jitter']['Rendah']) for x in x_jitter], 'r', label='Rendah')
    ax2.plot(x_jitter, [triangle(x, *sets['jitter']['Sedang']) for x in x_jitter], 'g', label='Sedang')
    ax2.plot(x_jitter, [trapezoid(x, *sets['jitter']['Tinggi']) for x in x_jitter], 'b', label='Tinggi')
    ax2.axvline(x=jitter_val, color='k', linestyle='--', label=f'Input Jitter = {jitter_val}')
    ax2.set_title('Fungsi Keanggotaan Jitter (ms)')
    ax2.legend()
    
    # Plot 3: Loss 
    x_loss = np.linspace(0, 10, 500) # Kita set sumbu x dari 0 sampai 10%
    ax3.plot(x_loss, [trapezoid(x, *sets['loss']['Rendah']) for x in x_loss], 'r', label='Rendah')
    ax3.plot(x_loss, [triangle(x, *sets['loss']['Sedang']) for x in x_loss], 'g', label='Sedang')
    ax3.plot(x_loss, [trapezoid(x, *sets['loss']['Tinggi']) for x in x_loss], 'b', label='Tinggi')
    ax3.axvline(x=loss_val, color='k', linestyle='--', label=f'Input Loss = {loss_val}%')
    ax3.set_title('Fungsi Keanggotaan Loss (%)')
    ax3.legend()

    
    # Plot 4: Agregasi & Defuzzifikasi
    x_output = np.linspace(0, 100, 100)
    y_buruk = np.array([trapezoid(x, *sets['output']['Buruk']) for x in x_output])
    y_cukup = np.array([triangle(x, *sets['output']['Cukup']) for x in x_output])
    y_baik = np.array([triangle(x, *sets['output']['Baik']) for x in x_output])
    y_sangat_baik = np.array([trapezoid(x, *sets['output']['Sangat Baik']) for x in x_output])

    # "Potong" bentuk output
    clipped_buruk = np.minimum(inferred_qos['Buruk'], y_buruk)
    clipped_cukup = np.minimum(inferred_qos['Cukup'], y_cukup)
    clipped_baik = np.minimum(inferred_qos['Baik'], y_baik)
    clipped_sangat_baik = np.minimum(inferred_qos['Sangat Baik'], y_sangat_baik)

    # Agregasi
    aggregated = np.maximum(clipped_buruk, np.maximum(clipped_cukup, np.maximum(clipped_baik, clipped_sangat_baik)))
    
    ax4.plot(x_output, y_buruk, color='red', linestyle=':', alpha=0.5)
    ax4.plot(x_output, y_cukup, color='green', linestyle=':', alpha=0.5)
    ax4.plot(x_output, y_baik, color='blue', linestyle=':', alpha=0.5)
    ax4.plot(x_output, y_sangat_baik, color='purple', linestyle=':', alpha=0.5)

    ax4.fill_between(x_output, aggregated, color='orange', alpha=0.7, label='Area Agregasi')
    ax4.set_title('Agregasi & Defuzzifikasi (Centroid)')
    ax4.legend()

    plt.tight_layout()

    buf = io.BytesIO()
    plt.savefig(buf, format='png')
    buf.seek(0)
    img_str = base64.b64encode(buf.read()).decode('utf-8')
    return img_str

if __name__ == "__main__":
    delay = float(sys.argv[1])
    jitter = float(sys.argv[2])
    loss = float(sys.argv[3])
    base64_image = generate_plot(delay, jitter, loss)
    print(base64_image)

