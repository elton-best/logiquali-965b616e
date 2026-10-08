export interface RatingOption {
  value: number
  title: string
}

export function useRatingOptions () {
  const ratingOptions0to5: RatingOption[] = [
    { value: 0, title: '0 - Insuffisant' },
    { value: 1, title: '1 - Faible' },
    { value: 2, title: '2 - Moyen' },
    { value: 3, title: '3 - Satisfaisant' },
    { value: 4, title: '4 - Bon' },
    { value: 5, title: '5 - Excellent' },
  ]

  const ratingOptions1to5: RatingOption[] = [
    { value: 1, title: '1 - Insatisfait' },
    { value: 2, title: '2 - Moyennement satisfait' },
    { value: 3, title: '3 - Satisfait' },
    { value: 4, title: '4 - Très satisfait' },
    { value: 5, title: '5 - Excellent' },
  ]

  const ratingOptions1to4: RatingOption[] = [
    { value: 1, title: '1 - Très insatisfait' },
    { value: 2, title: '2 - Insatisfait' },
    { value: 3, title: '3 - Satisfait' },
    { value: 4, title: '4 - Très satisfait' },
  ]

  const ratingOptions1to3: RatingOption[] = [
    { value: 1, title: '1 - Insatisfait' },
    { value: 2, title: '2 - Moyennement satisfait' },
    { value: 3, title: '3 - Satisfait' },
  ]

  return {
    ratingOptions0to5,
    ratingOptions1to5,
    ratingOptions1to4,
    ratingOptions1to3,
  }
}
