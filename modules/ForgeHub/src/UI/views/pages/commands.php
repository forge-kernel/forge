<?php
$commandHistory = $_SESSION['command_history'] ?? [];
$whoami = $whoami ?? 'user';
$pwd = $pwd ?? '/';
?>
<div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-12rem)]">
  <div class="flex flex-col w-full bg-white rounded-lg border border-gray-200 shadow-sm lg:w-80">
    <div class="p-4 border-b border-gray-200">
      <h2 class="mb-3 text-lg font-semibold text-gray-900">Commands</h2>
      <div class="relative">
        <input type="text" id="commandSearch" placeholder="Search commands..."
          class="px-3 py-2 pl-10 w-full text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <svg class="absolute top-2.5 left-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
          viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
    </div>
    <div class="overflow-y-auto flex-1 p-4" id="commandsList">
      <div class="py-8 text-sm text-center text-gray-500">Loading commands...</div>
    </div>
  </div>

  <div class="flex overflow-hidden flex-col flex-1 bg-white rounded-lg border border-gray-200 shadow-sm">
    <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border-b border-gray-200">
      <div class="flex gap-2 items-center font-mono text-sm text-gray-600">
        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd" />
        </svg>
        <span><?= htmlspecialchars($whoami ?? 'user') ?> - <?= htmlspecialchars($pwd ?? '/') ?></span>
      </div>
      <div class="text-xs text-gray-500">Forge CLI</div>
    </div>

    <div class="overflow-y-auto flex-1 p-4 font-mono text-sm text-gray-900 bg-gray-900" id="cliOutput">
      <div class="italic text-gray-500">Ready to execute commands...</div>
    </div>

    <div class="p-4 bg-gray-50 border-t border-gray-200">
      <form id="commandForm" class="flex gap-2">
        <span class="pt-2 font-mono font-semibold text-green-500">$</span>
        <input type="text" name="command" id="command"
          class="flex-1 px-3 py-2 font-mono text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="Enter Forge command or select from sidebar" autocomplete="off">
        <?= component(name: 'ForgeHub:button', props: ['type' => 'submit', 'variant' => 'primary', 'children' => 'Execute', 'class' => 'bg-blue-600 hover:bg-blue-700']) ?>
      </form>
      <div id="argumentForm" class="hidden mt-3">
        <label class="block mb-2 text-sm font-medium text-gray-700">Command Arguments</label>
        <div id="argumentsContainer" class="space-y-3"></div>
        <div class="flex gap-2 mt-3">
          <button type="button" id="clearArgs"
            class="px-3 py-2 text-sm text-gray-600 rounded-lg border border-gray-300 hover:text-gray-900 hover:bg-gray-50">Clear</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const commandInput = document.getElementById('command');
  const commandForm = document.getElementById('commandForm');
  const cliOutput = document.getElementById('cliOutput');
  const commandsList = document.getElementById('commandsList');
  const commandSearch = document.getElementById('commandSearch');
  const argumentForm = document.getElementById('argumentForm');
  const commandArgs = document.getElementById('commandArgs');
  const clearArgs = document.getElementById('clearArgs');

  let historyIndex = -1;
  const commandHistory = <?php echo json_encode(array_reverse($commandHistory)); ?>;
  let tempInput = '';
  let activeProcessId = null;
  let pollInterval = null;
  let allCommands = {};
  let selectedCommand = '';

  function loadCommands() {
    fetch('/hub/commands/list', {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': window.csrfToken || ''
      }
    })
      .then(response => {
        if (!response.ok) {
          return response.text().then(text => {
            throw new Error('Failed to load commands: ' + text);
          });
        }
        return response.json();
      })
      .then(response => {
        const data = response.data || response;
        allCommands = data.commands || {};
        if (Object.keys(allCommands).length === 0) {
          commandsList.innerHTML = '<div class="py-8 text-sm text-center text-yellow-600">No commands available. Check server logs for errors.</div>';
        } else {
          renderCommands(allCommands);
        }
      })
      .catch(error => {
        console.error('Error loading commands:', error);
        commandsList.innerHTML = '<div class="py-8 text-sm text-center text-red-500">Error loading commands: ' + escapeHtml(error.message) + '<br><small>Check browser console for details</small></div>';
      });
  }

  function renderCommands(commands, searchTerm = '') {
    if (Object.keys(commands).length === 0) {
      commandsList.innerHTML = '<div class="py-8 text-sm text-center text-gray-500">No commands available</div>';
      return;
    }

    const filtered = searchTerm ? filterCommands(commands, searchTerm) : commands;

    if (Object.keys(filtered).length === 0) {
      commandsList.innerHTML = '<div class="py-8 text-sm text-center text-gray-500">No commands found</div>';
      return;
    }

    let html = '';
    for (const [category, categoryCommands] of Object.entries(filtered)) {
      html += `<div class="mb-6">`;
      html += `<h3 class="mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">${escapeHtml(category)}</h3>`;
      html += `<ul class="space-y-1">`;
      for (const [cmdName, description] of Object.entries(categoryCommands)) {
        const isSelected = selectedCommand === cmdName ? 'bg-blue-50 border-blue-200' : 'border-gray-200 hover:bg-gray-50';
        html += `<li>`;
        html += `<button type="button" class="w-full text-left px-3 py-2 text-sm rounded-lg border ${isSelected} transition-colors command-item" data-command="${escapeHtml(cmdName)}">`;
        html += `<div class="font-medium text-gray-900">${escapeHtml(cmdName)}</div>`;
        html += `<div class="mt-0.5 text-xs text-gray-500">${escapeHtml(description || 'No description')}</div>`;
        html += `</button>`;
        html += `</li>`;
      }
      html += `</ul>`;
      html += `</div>`;
    }
    commandsList.innerHTML = html;

    document.querySelectorAll('.command-item').forEach(btn => {
      btn.addEventListener('click', function () {
        const cmd = this.dataset.command;
        selectCommand(cmd);
      });
    });
  }

  function filterCommands(commands, searchTerm) {
    const filtered = {};
    const term = searchTerm.toLowerCase();
    for (const [category, categoryCommands] of Object.entries(commands)) {
      const filteredCommands = {};
      for (const [cmdName, description] of Object.entries(categoryCommands)) {
        if (cmdName.toLowerCase().includes(term) || (description && description.toLowerCase().includes(term))) {
          filteredCommands[cmdName] = description;
        }
      }
      if (Object.keys(filteredCommands).length > 0) {
        filtered[category] = filteredCommands;
      }
    }
    return filtered;
  }

  function selectCommand(cmd) {
    selectedCommand = cmd;
    commandInput.value = cmd;
    loadCommandArguments(cmd);
    renderCommands(allCommands, commandSearch.value);
  }

  function loadCommandArguments(cmd) {
    fetch(`/hub/commands/arguments?command=${encodeURIComponent(cmd)}`, {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': window.csrfToken || ''
      }
    })
      .then(response => response.json())
      .then(response => {
        const data = response.data || response;
        const arguments = data.arguments || [];
        renderArguments(arguments);
      })
      .catch(error => {
        console.error('Error loading command arguments:', error);
        argumentForm.classList.add('hidden');
      });
  }

  function renderArguments(args) {
    let argsContainer = document.getElementById('argumentsContainer');
    if (!argsContainer) {
      argsContainer = document.createElement('div');
      argsContainer.id = 'argumentsContainer';
      argsContainer.className = 'space-y-3';
      argumentForm.appendChild(argsContainer);
    } else {
      argsContainer.innerHTML = '';
    }

    if (args.length === 0) {
      argumentForm.classList.add('hidden');
      return;
    }

    argumentForm.classList.remove('hidden');

    args.forEach(arg => {
      const argDiv = document.createElement('div');
      argDiv.className = 'space-y-1';

      const label = document.createElement('label');
      label.className = 'block text-sm font-medium text-gray-700';
      label.textContent = `--${arg.name}`;
      if (arg.required) {
        const required = document.createElement('span');
        required.className = 'text-red-500 ml-1';
        required.textContent = '*';
        label.appendChild(required);
      }
      if (arg.description) {
        const desc = document.createElement('span');
        desc.className = 'text-xs text-gray-500 ml-2';
        desc.textContent = `(${arg.description})`;
        label.appendChild(desc);
      }

      const input = document.createElement('input');
      input.type = 'text';
      input.name = `arg_${arg.name}`;
      input.dataset.argName = arg.name;
      input.dataset.required = arg.required ? 'true' : 'false';
      input.className = 'w-full bg-white text-gray-900 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm';
      input.placeholder = arg.default !== null ? `Default: ${arg.default}` : (arg.required ? 'Required' : 'Optional');
      if (arg.default !== null && !arg.required) {
        input.value = arg.default;
      }

      argDiv.appendChild(label);
      argDiv.appendChild(input);
      argsContainer.appendChild(argDiv);
    });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function stripAnsiCodes(text) {
    return text.replace(/\u001b\[[0-9;]*m/g, '');
  }

  commandSearch.addEventListener('input', function () {
    renderCommands(allCommands, this.value);
  });

  clearArgs.addEventListener('click', function () {
    const container = document.getElementById('argumentsContainer');
    if (container) {
      Array.from(container.querySelectorAll('input')).forEach(input => {
        input.value = '';
        input.classList.remove('border-red-500');
      });
    }
    argumentForm.classList.add('hidden');
    selectedCommand = '';
    commandInput.value = '';
    renderCommands(allCommands, commandSearch.value);
  });

  commandInput.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (historyIndex < commandHistory.length - 1) {
        if (historyIndex === -1) {
          tempInput = commandInput.value;
        }
        historyIndex++;
        commandInput.value = commandHistory[historyIndex];
      }
    } else if (event.key === 'ArrowDown') {
      event.preventDefault();
      if (historyIndex > -1) {
        historyIndex--;
        if (historyIndex === -1) {
          commandInput.value = tempInput;
        } else {
          commandInput.value = commandHistory[historyIndex];
        }
      }
    } else if (event.key === 'Enter') {
      event.preventDefault();
      historyIndex = -1;
      tempInput = '';
      submitCommandForm();
    } else {
      if (selectedCommand && commandInput.value !== selectedCommand) {
        selectedCommand = '';
        argumentForm.classList.add('hidden');
        const container = document.getElementById('argumentsContainer');
        if (container) {
          container.innerHTML = '';
        }
        renderCommands(allCommands, commandSearch.value);
      } else if (!selectedCommand && commandInput.value.trim()) {
        const cmd = commandInput.value.trim().split(' ')[0];
        loadCommandArguments(cmd);
      }
    }
  });

  commandForm.addEventListener('submit', function (event) {
    event.preventDefault();
    submitCommandForm();
  });

  document.addEventListener('submit', function (event) {
    const form = event.target;
    if (form.action && form.action.includes('/hub/commands/send-input')) {
      event.preventDefault();
      submitInputForm(form);
    }
  });

  function submitCommandForm() {
    let command = commandInput.value.trim();

    if (!command) return;

    const argsContainer = document.getElementById('argumentsContainer');
    if (argsContainer && !argsContainer.classList.contains('hidden') && argsContainer.children.length > 0) {
      const args = [];
      const errors = [];

      Array.from(argsContainer.querySelectorAll('input')).forEach(input => {
        const argName = input.dataset.argName;
        const required = input.dataset.required === 'true';
        const value = input.value.trim();

        if (required && !value) {
          errors.push(`--${argName} is required`);
          input.classList.add('border-red-500');
        } else {
          input.classList.remove('border-red-500');
          if (value) {
            args.push(`--${argName}=${value}`);
          }
        }
      });

      if (errors.length > 0) {
        alert('Missing required arguments:\n' + errors.join('\n'));
        return;
      }

      if (args.length > 0) {
        command = command + ' ' + args.join(' ');
      } else {
        const hasRequired = Array.from(argsContainer.querySelectorAll('input')).some(
          input => input.dataset.required === 'true'
        );
        if (hasRequired) {
          alert('This command requires arguments. Please fill in all required fields.');
          return;
        }
      }
    }

    const cmdLineDiv = document.createElement('div');
    cmdLineDiv.className = 'text-green-400 mb-2 font-semibold';
    cmdLineDiv.textContent = '$ ' + escapeHtml(command);
    cliOutput.appendChild(cmdLineDiv);

    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'text-gray-400 italic mb-2';
    loadingDiv.textContent = 'Executing command...';
    cliOutput.appendChild(loadingDiv);

    cliOutput.scrollTop = cliOutput.scrollHeight;
    commandInput.value = '';
    if (argsContainer) {
      Array.from(argsContainer.querySelectorAll('input')).forEach(input => {
        input.value = '';
        input.classList.remove('border-red-500');
      });
    }
    argumentForm.classList.add('hidden');
    selectedCommand = '';
    commandInput.disabled = true;

    const formData = new FormData();
    formData.set('command', command);
    formData.set('_token', window.csrfToken || '');

    fetch('/hub/commands/execute', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': window.csrfToken || ''
      },
      body: formData
    })
      .then(response => {
        if (!response.ok) {
          return response.text().then(text => {
            try {
              return JSON.parse(text);
            } catch {
              throw new Error(text || 'Request failed');
            }
          });
        }
        return response.json();
      })
      .then(response => {
        const data = response.data || response;

        if (loadingDiv.parentNode) {
          loadingDiv.parentNode.removeChild(loadingDiv);
        }

        if (data.status === 'error') {
          const errorDiv = document.createElement('div');
          errorDiv.className = 'text-red-400 mb-2';
          errorDiv.textContent = stripAnsiCodes(data.output || 'Error executing command');
          cliOutput.appendChild(errorDiv);
          commandInput.disabled = false;
          commandInput.focus();
          cliOutput.scrollTop = cliOutput.scrollHeight;
          return;
        }

        activeProcessId = data.processId;

        if (data.output) {
          const outputDiv = document.createElement('div');
          outputDiv.className = 'text-gray-100 whitespace-pre-wrap mb-4 font-mono text-sm';
          const cleanOutput = stripAnsiCodes(data.output);
          outputDiv.textContent = cleanOutput;
          cliOutput.appendChild(outputDiv);
        }

        if (data.status === 'running' || data.status === 'waiting_for_input') {
          if (data.needsInput && data.prompt && data.processId) {
            createInputForm(data.prompt, data.processId);
          } else {
            startPolling(activeProcessId);
          }
        } else {
          commandInput.disabled = false;
          commandInput.focus();
        }

        cliOutput.scrollTop = cliOutput.scrollHeight;
      })
      .catch(error => {
        console.error('Error executing command:', error);
        if (loadingDiv.parentNode) {
          loadingDiv.textContent = 'Error: ' + error.message;
          loadingDiv.className = 'text-red-400';
        }
        commandInput.disabled = false;
        commandInput.focus();
      });
  }

  function submitInputForm(form) {
    const formData = new FormData(form);
    const input = form.querySelector('input[name="input"]');
    const inputValue = input.value;

    const userInputDiv = document.createElement('div');
    userInputDiv.className = 'text-yellow-400 mb-2 font-semibold';
    userInputDiv.textContent = escapeHtml(inputValue);
    cliOutput.appendChild(userInputDiv);

    input.value = '';

    formData.set('_token', window.csrfToken || '');

    fetch('/hub/commands/send-input', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': window.csrfToken || ''
      },
      body: formData
    })
      .then(response => {
        if (!response.ok) {
          return response.text().then(text => {
            try {
              return JSON.parse(text);
            } catch {
              throw new Error(text || 'Request failed');
            }
          });
        }
        return response.json();
      })
      .then(response => {
        const data = response.data || response;
        form.remove();

        if (data.output) {
          const outputDiv = document.createElement('div');
          outputDiv.className = 'text-gray-100 whitespace-pre-wrap mb-4 font-mono text-sm';
          const cleanOutput = stripAnsiCodes(data.output);
          outputDiv.textContent = cleanOutput;
          cliOutput.appendChild(outputDiv);
        }

        if (data.needsInput && data.prompt && data.processId) {
          createInputForm(data.prompt, data.processId);
        } else {
          commandInput.disabled = false;
          commandInput.focus();
          if (pollInterval) {
            clearInterval(pollInterval);
          }
        }

        cliOutput.scrollTop = cliOutput.scrollHeight;
      })
      .catch(error => {
        console.error('Error sending input:', error);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-400 mb-2';
        errorDiv.textContent = 'Error: ' + escapeHtml(error.message || 'Failed to send input');
        cliOutput.appendChild(errorDiv);
        commandInput.disabled = false;
        commandInput.focus();
        cliOutput.scrollTop = cliOutput.scrollHeight;
      });
  }

  function createInputForm(prompt, processId) {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/hub/commands/send-input';
    form.className = 'mt-4 flex gap-2 items-center';

    const label = document.createElement('label');
    label.className = 'text-yellow-400 font-semibold';
    label.textContent = escapeHtml(prompt) + ' ';

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'input';
    input.className = 'flex-1 bg-gray-800 text-gray-100 border border-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm';
    input.placeholder = 'Enter your response';
    input.autocomplete = 'off';

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'process_id';
    hiddenInput.value = processId;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = window.csrfToken || '';

    const button = document.createElement('button');
    button.type = 'submit';
    button.className = 'px-4 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium';
    button.textContent = 'Send';

    form.appendChild(label);
    form.appendChild(input);
    form.appendChild(hiddenInput);
    form.appendChild(csrfInput);
    form.appendChild(button);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      submitInputForm(form);
    });

    cliOutput.appendChild(form);
    input.focus();
    cliOutput.scrollTop = cliOutput.scrollHeight;
  }

  function startPolling(processId) {
    if (pollInterval) {
      clearInterval(pollInterval);
    }
    pollInterval = setInterval(async () => {
      try {
        const response = await fetch(`/hub/commands/status?process_id=${processId}`);
        const data = await response.json();

        if (data.status === 'completed' || data.status === 'error' || data.status === 'timeout') {
          clearInterval(pollInterval);
          commandInput.disabled = false;
          commandInput.focus();
        }
      } catch (error) {
        console.error('Error polling command status:', error);
        clearInterval(pollInterval);
        commandInput.disabled = false;
        commandInput.focus();
      }
    }, 1000);
  }

  loadCommands();
  commandInput.focus();
</script>
