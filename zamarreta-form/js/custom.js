document.getElementById("unidades").addEventListener('change', doThing);

function doThing(){

    let value = this.value;

    const div = document.createElement('div');

    div.className = 'row';
    div.innerHTML = "Dinos la talla de tus zamarretas</br>"
    for(let i = 0; i < value; i++){
     
        div.innerHTML += `      
        <label for="tallas">Talla:</label>
        <select name="talla[]" form="zamarreta" required>
        <option value="S">S</option>
        <option value="M">M</option>
        <option value="L">L</option>
      `;
    }
  
    document.getElementById("mostradorTallas").replaceChildren(div);
}

