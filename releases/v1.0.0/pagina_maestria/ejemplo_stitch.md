# Ejemplo Práctico: Usar Stitch en un archivo Markdown (.md)

Este es un ejemplo de cómo podemos estructurar el código que te genere **Google Stitch** dentro de un archivo `.md` (Markdown) para revisarlo juntos, o cómo podemos usar **Material Design (MD)** si a eso te referías.

## 1. El Prompt para diseño en Stitch
Imagina que fuiste a *stitch.withgoogle.com* y le pediste:
> "Crea el encabezado principal (Hero Section) para la Página de la Maestría en Física Atómica, usando un botón de inscripción destacado."

## 2. El Código (Componente) que te da Stitch
Stitch te exportará algo como esto. Tú solo lo pegas aquí, o en un archivo similar, y lo revisamos:

```html
<div class="mdc-hero" style="background-color: #0b1a3d; color: white; padding: 4rem 2rem; text-align: center; border-radius: 8px;">
  <h1 class="mdc-typography--headline3">
    Maestría en Física Atómica
  </h1>
  <p class="mdc-typography--body1" style="max-width: 600px; margin: 1rem auto;">
    Explora la frontera de la ciencia subatómica. Inscripciones abiertas para el ciclo 2026-1 en nuestro campus principal.
  </p>
  <button class="mdc-button mdc-button--raised" style="background-color: #ff5722; color: white; padding: 0.5rem 1.5rem;">
    <span class="mdc-button__label">Inscríbete Ahora</span>
  </button>
</div>
```

## 3. ¿Cómo lo usamos?
* **Paso 1:** Generas el diseño en Stitch y lo exportas.
* **Paso 2:** Lo guardas en un archivo Markdown (`.md`) como este para la documentación de tu proyecto, o lo guardamos directamente como un archivo `.html` / `.jsx` dentro de tu carpeta `pagina_maestria`.
* **Paso 3:** ¡Me pides que lo implemente! Yo tomaré el código exacto, instalaré el framework (Ej. React o Tailwind), haré que la página cargue, conectaré bases de datos si la inscripción debe guardarse en un CSV u hojas de cálculo, y dejaré la página funcional.

*Nota: Si por "MD" te referías a **Material Design** (el sistema de diseño de Google), ¡estamos en sintonía! Stitch es experto en generar componentes visuales siguiendo las reglas de Material Design.*
