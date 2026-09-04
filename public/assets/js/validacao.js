/**
 * Forno do Bairro — Validação de formulários (client-side)
 * ETAPA 1 — usa apenas o atributo `data-validate` nos campos.
 *
 * Regras suportadas (separadas por |):
 *   required              campo obrigatório
 *   email                 formato de e-mail
 *   min:N                 tamanho mínimo de N caracteres
 *   max:N                 tamanho máximo de N caracteres
 *   telefone              formato (00) 00000-0000
 *   senha                 mínimo 6 caracteres, 1 letra e 1 número
 *   confirma:#id          precisa ser igual ao valor do campo #id
 *   checked                checkbox precisa estar marcado
 */

const Validador = (() => {

  const MENSAGENS = {
    required: 'Este campo é obrigatório.',
    email: 'Digite um e-mail válido.',
    telefone: 'Use o formato (00) 00000-0000.',
    senha: 'A senha precisa ter ao menos 6 caracteres, com letras e números.',
    checked: 'Você precisa marcar esta opção para continuar.',
  };

  function regexEmail(valor) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor);
  }

  function regexTelefone(valor) {
    return /^\(\d{2}\)\s?\d{4,5}-\d{4}$/.test(valor);
  }

  function regexSenha(valor) {
    return /^(?=.*[A-Za-z])(?=.*\d).{6,}$/.test(valor);
  }

  /** Aplica máscara de telefone brasileiro enquanto o usuário digita. */
  function mascararTelefone(input) {
    input.addEventListener('input', () => {
      let v = input.value.replace(/\D/g, '').slice(0, 11);
      if (v.length > 6) {
        v = v.replace(/^(\d{2})(\d{4,5})(\d{0,4}).*/, '($1) $2-$3');
      } else if (v.length > 2) {
        v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
      } else if (v.length > 0) {
        v = v.replace(/^(\d*)/, '($1');
      }
      input.value = v;
    });
  }

  function obterMensagemErro(campo, regra) {
    const customMsg = campo.dataset[`msg${regra.charAt(0).toUpperCase()}${regra.slice(1)}`];
    return customMsg || MENSAGENS[regra] || 'Valor inválido.';
  }

  function validarCampo(campo) {
    const regrasAttr = campo.dataset.validate;
    if (!regrasAttr) return true;

    const regras = regrasAttr.split('|');
    const valor = campo.type === 'checkbox' ? campo.checked : campo.value.trim();
    const wrapper = campo.closest('.field') || campo.parentElement;
    const erroEl = wrapper ? wrapper.querySelector('.error-msg') : null;

    for (const regraCompleta of regras) {
      const [regra, param] = regraCompleta.split(':');
      let valido = true;
      let mensagem = obterMensagemErro(campo, regra);

      switch (regra) {
        case 'required':
          valido = campo.type === 'checkbox' ? valor === true : valor !== '';
          break;
        case 'email':
          valido = valor === '' || regexEmail(valor);
          break;
        case 'telefone':
          valido = valor === '' || regexTelefone(valor);
          break;
        case 'senha':
          valido = valor === '' || regexSenha(valor);
          break;
        case 'min':
          valido = valor === '' || valor.length >= parseInt(param, 10);
          mensagem = `Digite ao menos ${param} caracteres.`;
          break;
        case 'max':
          valido = valor.length <= parseInt(param, 10);
          mensagem = `Digite no máximo ${param} caracteres.`;
          break;
        case 'checked':
          valido = campo.checked;
          break;
        case 'confirma': {
          const outro = document.querySelector(param);
          valido = outro ? campo.value === outro.value : true;
          mensagem = 'Os valores não coincidem.';
          break;
        }
        default:
          valido = true;
      }

      if (!valido) {
        if (wrapper) wrapper.classList.add('is-invalid');
        if (wrapper) wrapper.classList.remove('is-valid');
        if (erroEl) erroEl.textContent = mensagem;
        return false;
      }
    }

    if (wrapper) {
      wrapper.classList.remove('is-invalid');
      if (valor !== '' && valor !== false) wrapper.classList.add('is-valid');
    }
    return true;
  }

  function validarFormulario(form) {
    const campos = form.querySelectorAll('[data-validate]');
    let formValido = true;
    campos.forEach((campo) => {
      const ok = validarCampo(campo);
      if (!ok) formValido = false;
    });
    return formValido;
  }

  function iniciar(formId, aoEnviarValido) {
    const form = document.getElementById(formId);
    if (!form) return;

    // Aplica máscara automaticamente em campos de telefone.
    form.querySelectorAll('[data-validate*="telefone"]').forEach(mascararTelefone);

    // Validação em tempo real (blur / input após primeira tentativa).
    form.querySelectorAll('[data-validate]').forEach((campo) => {
      campo.addEventListener('blur', () => validarCampo(campo));
      campo.addEventListener('input', () => {
        if (campo.closest('.field')?.classList.contains('is-invalid')) {
          validarCampo(campo);
        }
      });
    });

    form.addEventListener('submit', (evento) => {
      const valido = validarFormulario(form);
      if (!valido) {
        evento.preventDefault();
        const primeiroErro = form.querySelector('.is-invalid');
        if (primeiroErro) {
          primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
      }
      if (typeof aoEnviarValido === 'function') {
        return aoEnviarValido(evento, form);
      }
    });
  }

  return { iniciar, validarCampo, validarFormulario };
})();
