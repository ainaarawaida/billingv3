<div>
    <div style="margin-bottom: 50px;">
        <div class="text-center mb-10 md:mb-0">
            Developed @
            <a href="#" class="text-blue-500 hover:text-blue-700 font-bold">4in44</a>
    
        </div>
    </div>
    
    <footer class="flex justify-between fixed bottom-0 left-0 z-20 w-full p-2 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-2 dark:bg-gray-800 dark:border-gray-600">
        <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">© {{ date('Y') }}
            <a href="#" class="hover:underline">4in44 Site™</a>. All Rights Reserved.
        </span>
        <div class="flex">
            <img alt="logo" src="{{ url('assets/logo.png') }}" style="width: 30px;" class="fi-logo justify-self-end">
        </div>
    </footer>

</div>

<script data-navigate-once>
    document?.addEventListener("livewire:navigated", () => {
        document?.querySelector("table")?.addEventListener("click", async (e) => {
            const selectedEle = e.target.closest(".copy-public_url");
           
          if(selectedEle){
              let linkToCopy = selectedEle.getAttribute("myurl");
              
              try {
                    await copyToClipboard(linkToCopy);
                } catch(error) {
                    console.error(error);
                }
             
            }
        })
        async function copyToClipboard(textToCopy) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(textToCopy);
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = textToCopy;
                    
                textArea.style.position = "absolute";
                textArea.style.left = "-999999px";
                    
                document.body.prepend(textArea);
                textArea.select();
        
                try {
                    document.execCommand("copy");
                } catch (error) {
                    console.error(error);
                } finally {
                    textArea.remove();
                }
            }
        }
      
    })
  
  </script>

