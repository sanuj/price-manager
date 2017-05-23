export function zip(...arrays) {
  const maxLength = () => arrays.reduce((m, array) => Math.max(m, array.length), 0)
  const minLength = () => arrays.reduce((m, array) => Math.min(m, array.length), 0)
  const kth = k => arrays.map(array => array[k])
  const map = callback => {
    let i = 0
    const n = maxLength()
    const result = []
    while (i < n) {
      result.push(...kth(i), i)
      ++i
    }

    return result
  }

  return { maxLength, minLength, kth, map }
}