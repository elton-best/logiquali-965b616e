import { nextTick } from 'vue'

type FocusableElement = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
type MaybeComponentRef = HTMLElement | { $el?: HTMLElement } | null | undefined

function resolveFocusableElement (target: MaybeComponentRef): FocusableElement | null {
  if (!target) {
    return null
  }

  const root = target instanceof HTMLElement ? target : target.$el
  if (!root) {
    return null
  }

  if (root instanceof HTMLInputElement || root instanceof HTMLTextAreaElement || root instanceof HTMLSelectElement) {
    return root
  }

  return root.querySelector('textarea, input, select')
}

export async function focusTopInsertedField (
  refs: Array<MaybeComponentRef>,
  index = 0,
) {
  await nextTick()

  requestAnimationFrame(() => {
    const element = resolveFocusableElement(refs[index])
    if (!element) {
      return
    }

    element.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
      inline: 'nearest',
    })

    element.focus()

    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
      element.select?.()
    }
  })
}
