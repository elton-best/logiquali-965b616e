declare module 'lodash' {
  export function debounce<T extends (...args: any[]) => any> (
    func: T,
    wait?: number,
  ): (...args: Parameters<T>) => ReturnType<T>
}

declare module 'lodash-es' {
  export function debounce<T extends (...args: any[]) => any> (
    func: T,
    wait?: number,
  ): (...args: Parameters<T>) => ReturnType<T>
}
