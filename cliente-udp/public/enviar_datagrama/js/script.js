const animation = bodymovin.loadAnimation({
    container: document.getElementById('animContainer'),
    renderer: 'svg',
    loop: true,
    autoplay: true,
    path: 'https://assets1.lottiefiles.com/private_files/lf30_4n39mwxz.json' // lottie file path
  })


const fileInput = document.getElementById('datagrama'); // Elemento input[type="file"]
const enviarButton = document.getElementById('enviar'); // Elemento button de envio

enviarButton.addEventListener('click', async (event) => {
event.preventDefault(); // Prevenir o comportamento padrão de envio do formulário

const file = fileInput.files[0];

try {
    //console.log(file);
    //return await sendDatagram(file); 
    const formData = new FormData(); // Cria uma instância de FormData
    formData.append('file', file); // Adiciona o arquivo ao formulário com um nome de campo
    console.log(formData);
    fetch('/send-data-grama',  {
      method: 'POST',
      body: formData
    }).then(response => response.json())
      .then(data => {
        console.log(data.teste);
        return data;
      })
      .catch(error => {
        return error
      }); 
    // Realize as ações adicionais desejadas com os chunks aqui
    
} catch (error) {
    console.error(error);
}
});

// const sendDatagram = async (datagram) => {
//     // Enviar o datagrama para o servidor
//     // ...
//     const formData = new FormData(); // Cria uma instância de FormData
//     formData.append('arquivo', datagram); // Adiciona o arquivo ao formulário com um nome de campo

//     fetch('/send-data-grama',  {
//       method: 'POST',
//       body: formData
//     }).then((response) => {
//       response = response.json();
//       console.log("teste: ", response);
//       return response;
//     })
//       .catch(error => {
//         return error
//       });
// }