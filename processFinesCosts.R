library(ggplot2)
library(dplyr)
library(scales)

plotTheme <- theme_minimal()+
  theme(plot.title = element_text(face="bold", hjust=.5, size=15),
        legend.position="top",
        #legend.title = element_blank(),
        axis.title = element_text(size=12, face="bold"),
        axis.text = element_text(size=12)
  )
  

# set the working directory to that of the script itself
this.dir <- dirname(parent.frame(2)$ofile)
setwd(this.dir)

# find and open our csv file
csvFile <- file.path("fcdata", "out.csv")
fcdata <- read.csv(csvFile, header=FALSE)
names(fcdata) <- c("First", "Last", "Address", "PaymentPlanNumber","Status", "DelinquentAmount", "TotalOwed", "MonthlyPayment")

#calculate some columns for later use
fcdata$MonthlyPaymentsBehind <- round(fcdata$DelinquentAmount / fcdata$MonthlyPayment, 3)
fcdata$DelinquentAmountPercentofTotalOwed <- round(fcdata$DelinquentAmount / fcdata$TotalOwed * 100,0)

dfStatus <- group_by(fcdata, Status)
summariseFcdata <- summarise_if(dfStatus[dfStatus$MonthlyPayment > 0,], is.numeric, median)

# start getting stats

# note that there are fewer people than entries since a person may appear multiple times. 
# to deal with that, counting total rows, total unique payment plan numbers, and total unique first, last name combos
totalEntries <- nrow(fcdata)
totalUniquePaymentPlans <- NROW(unique(fcdata$PaymentPlanNumber))
totalUniqueNames <- NROW(unique(fcdata[c("First","Last")]))
totalReferred <- NROW(fcdata[fcdata$Status == "Referred",])
totalActive <-  NROW(fcdata[fcdata$Status == "Active",])
totalOwed <- sum(fcdata$TotalOwed)
totalDelinquent <- sum(fcdata$DelinquentAmount)
totalOwedReferred <- sum(fcdata$TotalOwed[fcdata$Status=="Referred"])
totalDelinquentReferred <- sum(fcdata$DelinquentAmount[fcdata$Status=="Referred"])
totalNoAddress <- NROW(fcdata[fcdata$Address=="No valid address on record",])
totalNoAddressActive <- NROW(fcdata[fcdata$Address=="No valid address on record" & fcdata$Status=="Active",])
totalNoAddressReferred <- NROW(fcdata[fcdata$Address=="No valid address on record" & fcdata$Status=="Referred",])
proportionNoAddressReferred <- round(totalNoAddressReferred / totalReferred, 3)
proportionNoAddressActive <- round(totalNoAddressActive / totalActive, 3)
proportionNoAddress <-  round(totalNoAddress / totalEntries, 3)


# get stats about the money owed
statsDelAmt <- fivenum(fcdata$DelinquentAmount)
statsTotalOwed <- fivenum(fcdata$TotalOwed)
statsMonthlyPayment <- fivenum(fcdata$MonthlyPayment)
statsPaymentsBehind <- fivenum(fcdata$MonthlyPaymentsBehind)
statsDelinquentPercent <- fivenum(fcdata$DelinquentAmountPercentofTotalOwed)

#same for active and referred status
statsDelAmtReferred <- fivenum(fcdata$DelinquentAmount[fcdata$Status=="Referred"])
statsTotalOwedReferred <- fivenum(fcdata$TotalOwed[fcdata$Status=="Referred"])
statsMonthlyPaymentReferred <- fivenum(fcdata$MonthlyPayment[fcdata$Status=="Referred"])
statsPaymentsBehindReferred <- fivenum(fcdata$MonthlyPaymentsBehind[fcdata$Status=="Referred"])
statsDelinquentPercentReferred <- fivenum(fcdata$DelinquentAmountPercentofTotalOwed[fcdata$Status=="Referred"])

statsDelAmtActive <- fivenum(fcdata$DelinquentAmount[fcdata$Status=="Active"])
statsTotalOwedActive <- fivenum(fcdata$TotalOwed[fcdata$Status=="Active"])
statsMonthlyPaymentActive <- fivenum(fcdata$MonthlyPayment[fcdata$Status=="Active"])
statsPaymentsBehindActive <- fivenum(fcdata$MonthlyPaymentsBehind[fcdata$Status=="Active"])
statsDelinquentPercentActive <- fivenum(fcdata$DelinquentAmountPercentofTotalOwed[fcdata$Status=="Active"])


# denisty of monthly payments behind, categorized by status = Active or Referred.
# the denisty is liminted to people wiht a monthly payment (295 people have no monthly payment) and people behind by
# less than 300 months (623 are behind by that much)
subfcdata <- fcdata[fcdata$MonthlyPayment > 0 & fcdata$MonthlyPaymentsBehind < 200,]
plotMonthlyPaymentsBehind <- ggplot(subfcdata, aes(MonthlyPaymentsBehind, fill=Status)) + 
  geom_density(alpha=.5, position="identity") +
  geom_vline(data=summariseFcdata, aes(xintercept=MonthlyPaymentsBehind, colour=Status),
             linetype="dashed", size=2)+
  scale_x_continuous(breaks = sort(c(round(seq(0, 
                                         max(subfcdata$MonthlyPaymentsBehind), length.out=5),0),
                                     round(summariseFcdata$MonthlyPaymentsBehind),0))) +
  coord_cartesian(ylim=c(0,.04))+
  ggtitle("Distribution of FJD Payment Plan Monthly Payments Behind\nBy Payment Plan Status") +
  labs(x="Monthly Payments Behind (Dotted = Median)", y=NULL,
       caption="Graph does not include people behind by more than 200 payments (there are very few).\nY-axis cut at .04 as the number of Active Payment Plans 1 month or less behind dwarfs all other data\nData downloaded from http://courts.phila.gov/collections/index.asp on 10/27/2017") +
  plotTheme
#plotMonthlyPaymentsBehind  


# denisty of total amount delinlquent, categorized by status
# remving amounts > 3500 from the graph b/c there are only 3136 of them and they greatly skew our graph
# it may be smarter, ultimately, to have the Y axis as a log of the amount owed, but I can't figure out how to make the labels
# look nice since the range is so large
subfcdata <- fcdata[fcdata$DelinquentAmount < 3500,]
plotDelinquentAmount <- ggplot(subfcdata, aes(DelinquentAmount, fill=Status)) + 
  geom_density(alpha=.5, position="identity") +
  geom_vline(data=summariseFcdata, aes(xintercept=DelinquentAmount, colour=Status),
             linetype="dashed", size=2)+
  scale_x_continuous(breaks = sort(c(round(seq(0, 
                                               max(subfcdata$DelinquentAmount), length.out=5),0),
                                     round(summariseFcdata$DelinquentAmount),0)),
                     labels=dollar) +
  ggtitle("Distribution of FJD Amounts Deqlinquent\nBy Payment Plan Status") +
  labs(x="Delinquent Amount (Dotted = Median)", y=NULL,
       caption="Graph does not include people with more than $3500 delinquent as that makes the important part of the graph unreadable.\nData downloaded from http://courts.phila.gov/collections/index.asp on 10/27/2017") +
  plotTheme 
#plotDelinquentAmount  


# denisty of total owed, categorized by status
# it may be smarter, ultimately, to have the Y axis as a log of the amount owed, but I can't figure out how to make the labels
# look nice since the range is so large
subfcdata <- fcdata[fcdata$TotalOwed < 6000,]
plotTotalOwed <- ggplot(subfcdata, aes(TotalOwed, fill=Status)) + 
  geom_density(alpha=.5, position="identity") +
  geom_vline(data=summariseFcdata, aes(xintercept=TotalOwed, colour=Status),
             linetype="dashed", size=2)+
  scale_x_continuous(breaks = sort(c(round(seq(0, 
                                               max(subfcdata$TotalOwed), length.out=5),0),
                                     round(summariseFcdata$TotalOwed),0)),
                     labels=dollar) +
  ggtitle("Distribution of FJD Total Amounts Owed\nBy Payment Plan Status") +
  labs(x="Total Owed (Dotted = Median)", y=NULL,
       caption="Graph does not include people who owe more than $6000 as that makes the important part of the graph unreadable.\nData downloaded from http://courts.phila.gov/collections/index.asp on 10/27/2017") +
  plotTheme 
#plotTotalOwed  



# denisty of monthly payment amount, categorized by status
# Including only payments less than $600, because that is all but a couple thousand pplans and over that much skews the graph
# it may be smarter, ultimately, to have the Y axis as a log of the amount owed, but I can't figure out how to make the labels
# look nice since the range is so large
subfcdata <- fcdata[fcdata$MonthlyPayment < 601,]
plotMonthlyPayment <- ggplot(subfcdata, aes(MonthlyPayment,fill=Status)) + 
  geom_density(alpha=.5, position="identity") +
  geom_vline(data=summariseFcdata, aes(xintercept=MonthlyPayment, colour=Status),
             linetype="dashed", size=2)+
  scale_x_continuous(breaks = sort(c(round(seq(0, 
                                               max(subfcdata$MonthlyPayment), length.out=5),0),
                                     round(summariseFcdata$MonthlyPayment),0)),
                     labels=dollar) +
  coord_cartesian(ylim=c(0,.02))+
  ggtitle("Distribution of FJD Total Amounts Owed\nBy Payment Plan Status") +
  labs(x="Total Monthly Payment (Dotted = Median)", y=NULL,
       caption="Graph does not include people who have a payment plant over $600/month as that makes the important part of the graph unreadable.\nThe graph is cut off to exclude much of the data at the median of $25 and $35 since those are the vast, vast majority of payment plans.\nData downloaded from http://courts.phila.gov/collections/index.asp on 10/27/2017") +
  plotTheme 
#plotMonthlyPayment  
